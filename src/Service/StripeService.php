<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\StripeClient;
use Stripe\Invoice as StripeInvoice;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook as StripeWebhook;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class StripeService
{
    private StripeClient $client;
    private string $secretKey;
    private string $webhookSecret;
    private string $publicKey;

    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
        private DynamicMailer $dynamicMailer,
        string $stripeSecretKey,
        string $stripeWebhookSecret,
        string $stripePublicKey
    ) {
        $this->dynamicMailer = $dynamicMailer;
        $this->secretKey = $stripeSecretKey;
        $this->webhookSecret = $stripeWebhookSecret;
        $this->publicKey = $stripePublicKey;
        $this->client = new StripeClient($stripeSecretKey);
    }


    /**
     * Récupère les plans actifs (prices + products)
     *
     * @return array
     */
    public function getActivePlans(): array
    {
        $result = $this->client->prices->all([
            'active' => true,
            'expand' => ['data.product'],
        ]);

        return $result->data;
    }

    /**
     * Crée une session Checkout pour abonnement
     *
     * @param string $priceId
     * @param string $successUrl
     * @param string $cancelUrl
     * @param string $customerEmail
     * @return array
     */
    public function createSubscriptionSession(
        string $priceId,
        string $successUrl,
        string $cancelUrl,
        string $customerEmail
    ): array {
        $plans = $this->getActivePlans();
        $validPriceIds = array_map(fn($plan) => $plan->id, $plans);

        if (!in_array($priceId, $validPriceIds)) {
            throw new InvalidArgumentException("Le priceId fourni n'est pas valide ou n'est pas actif.");
        }

        $session = $this->client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'customer_email' => $customerEmail,
            'allow_promotion_codes' => true,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return [
            'id' => $session->id,
            'publicKey' => $this->publicKey,
        ];
    }

    /**
     * Traite les webhooks Stripe
     *
     * @param string $payload
     * @param string $signature
     * @return void
     */
    public function handleWebhook(string $payload, string $signature): void
    {
        try {
            $event = StripeWebhook::constructEvent($payload, $signature, $this->webhookSecret);
        } catch (Exception $e) {
            $this->logger->error("Signature de webhook invalide : " . $e->getMessage());
            throw new BadRequestHttpException('Invalid signature.');
        }

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $sessionId = $event->data->object->id;
                    $session = $this->client->checkout->sessions->retrieve($sessionId);
                    $this->onSessionCompleted($session);
                    $this->sendSubscriptionConfirmationEmail($session);
                    break;
                case 'invoice.paid':
                    $this->onInvoicePaid($event->data->object);
                    break;
                case 'invoice.payment_failed':
                    $this->onInvoicePaymentFailed($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->onSubscriptionCancelled($event->data->object);
                    break;

                case 'customer.subscription.updated':
                    $this->onSubscriptionUpdated($event->data->object);
                    break;
                default:
                    $this->logger->info("Événement Stripe non géré : " . $event->type);
            }
        } catch (Throwable $e) {
            $this->logger->error("Erreur lors du traitement de l’événement {$event->type} : " . $e->getMessage());
        }
    }


    /**
     * Gère checkout.session.completed
     *
     * @param StripeCheckoutSession $session
     * @return void
     */
    private function onSessionCompleted(StripeCheckoutSession $session): void
    {
        $this->logger->info("Traitement de checkout.session.completed pour la session {$session->id}");

        $email = $session->customer_email ?? 'N/A';
        $mode = $session->mode ?? 'undefined';
        $this->logger->info("Mode : {$mode} | Email client : {$email}");

        if ($mode !== 'subscription') {
            $this->logger->warning("Session {$session->id} ignorée : mode non pris en charge ({$mode})");
            return;
        }

        if (!$session->subscription) {
            $this->logger->error("Session {$session->id} en mode 'subscription' mais aucune subscription présente.");
            return;
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $this->logger->error("Utilisateur introuvable pour l'email {$email}");
            return;
        }

        $stripeSub = $this->client->subscriptions->retrieve($session->subscription);
        if (!$stripeSub) {
            $this->logger->error("Abonnement Stripe introuvable avec l’ID {$session->subscription}");
            return;
        }

        $existingSub = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSub->id]);
        if ($existingSub) {
            $this->logger->warning("Abonnement déjà existant : {$stripeSub->id} pour l'utilisateur {$user->getId()}");
            return;
        }

        $user->setStripeCustomerId($session->customer);
        $this->entityManager->persist($user);

        $sub = (new Subscription())
            ->setUser($user)
            ->setStripeSubscriptionId($stripeSub->id)
            ->setStart((new DateTime())->setTimestamp($stripeSub->items->data[0]->current_period_start))
            ->setEnd((new DateTime())->setTimestamp($stripeSub->items->data[0]->current_period_end))
            ->setPrice($stripeSub->items->data[0]->price->unit_amount / 100);

        $this->entityManager->persist($sub);
        $this->entityManager->flush();

        $this->logger->info("Abonnement Stripe {$stripeSub->id} enregistré pour l'utilisateur {$user->getId()}");
    }

    /**
     * Gère invoice.paid
     *
     * @param StripeInvoice $invoice
     * @return void
     */
    private function onInvoicePaid(StripeInvoice $invoice): void
    {
        $stripeSubId = $invoice->subscription;

        try {
            $stripeSub = $this->client->subscriptions->retrieve($stripeSubId);
        } catch (Throwable $e) {
            $this->logger->error("Impossible de récupérer l'abonnement Stripe #{$stripeSubId} : " . $e->getMessage());
            return;
        }

        $localSub = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubId]);

        if (!$localSub) {
            $this->logger->warning("Abonnement local introuvable pour Stripe #{$stripeSubId}");
            return;
        }

        $start = (new DateTime())->setTimestamp($stripeSub->current_period_start);
        $end = (new DateTime())->setTimestamp($stripeSub->current_period_end);

        $localSub->setStart($start);
        $localSub->setEnd($end);

        $this->entityManager->persist($localSub);
        $this->entityManager->flush();

        $this->logger->info("Abonnement {$stripeSubId} mis à jour en base avec dates : {$start->format('Y-m-d')} → {$end->format('Y-m-d')}");
    }


    /**
     * Récupère les détails d'une session Checkout
     *
     * @param string $sessionId
     * @return StripeCheckoutSession
     */
    public function getSessionDetails(string $sessionId): StripeCheckoutSession
    {
        return $this->client->checkout->sessions->retrieve(
            $sessionId,
            ['expand' => ['line_items.data.price.product']]
        );
    }

    /**
     * Récupère les détails d'un abonnement
     *
     * @param string $subscriptionId
     * @return void
     */
    public function getSubscriptionDetails(string $subscriptionId)
    {
        if (!$subscriptionId) {
            return null;
        }
        return $this->client->subscriptions->retrieve(
            $subscriptionId,
            ['expand' => ['items.data.price.product']]
        );
    }

    /**
     * Récupère les détails d'un client
     *
     * @param string $invoiceId
     * @return StripeInvoice
     */
    public function getCustomerDetails(string $customerId)
    {
        return $this->client->customers->retrieve($customerId);
    }

    /**
     * Retourne les price IDs et dates de fin des abonnements valides en base pour l'utilisateur
     *
     * @param User $user
     * @return array
     */
    public function getUserActiveSubscriptionData(User $user): array
    {
        $priceIds = [];
        $endDates = [];
        $stripeSubId = '';

        $stripeCustomerId = $user->getStripeCustomerId();
        if (!$stripeCustomerId) {
            return [
                'priceIds' => [],
                'endDates' => [],
                'subId' => '',
            ];
        }

        $stripeSubscriptions = $this->client->subscriptions->all([
            'customer' => $stripeCustomerId,
            'status' => 'all',
            'expand' => ['data.items.data.price']
        ]);

        foreach ($stripeSubscriptions->data as $subscription) {
            if (!in_array($subscription->status, ['active', 'trialing'])) {
                continue;
            }

            foreach ($subscription->items->data as $item) {
                $priceId = $item->price->id;
                $priceIds[] = $priceId;

                $end = (new DateTime())->setTimestamp($item->current_period_end);
                $endDates[$priceId] = $end;

                if (!$stripeSubId) {
                    $stripeSubId = $subscription->id;
                }
            }
        }

        return [
            'priceIds' => array_unique($priceIds),
            'endDates' => $endDates,
            'subId' => $stripeSubId,
        ];
    }


    /**
     * Envoie un email de confirmation d'abonnement
     *
     * @param \Stripe\Checkout\Session $session
     *
     * @param \Stripe\Checkout\Session $session
     * @return void
     */
    private function sendSubscriptionConfirmationEmail(\Stripe\Checkout\Session $session): void
    {
        try {
            $customerEmail = $session->customer_details->email ?? null;
            $firstName = $session->customer_details->name ?? 'Client';
            $subscriptionId = $session->subscription;

            if (!$customerEmail || !$subscriptionId) {
                $this->logger->warning("Impossible d’envoyer l’email de confirmation : email ou abonnement manquant.");
                return;
            }

            $subscription = $this->client->subscriptions->retrieve($subscriptionId);
            $price = $subscription->items->data[0]->price ?? null;
            $product = $price->product ? $this->client->products->retrieve($price->product) : null;

            $this->dynamicMailer->sendTemplatedEmail(
                to: $customerEmail,
                subject: $this->translator->trans("Confirmation de votre souscription à l’abonnement") . ' ' . ($product->name ?? ''),
                templatePath: 'email/stripe/confirmation_subscription.html.twig',
                templateVars: [
                    'firstName' => $firstName,
                    'planName' => $product->name ?? 'Formule',
                    'startDate' => (new \DateTime())->format('d/m/Y'),
                    'renewal' => $price->recurring->interval ?? 'mensuel',
                    'amount' => number_format($price->unit_amount / 100, 2, ',', ' ') . ' ' . strtoupper($price->currency),
                ]
            );
        } catch (Throwable $e) {
            $this->logger->error("Échec de l'envoi de l'email de confirmation d'abonnement : " . $e->getMessage());
        }
    }

    /**
     * Vérifie si l'utilisateur a un abonnement Stripe actif
     *
     * @param User $user
     *
     * @param User $user
     * @return boolean
     */
    public function hasActiveStripeSubscription(User $user): bool
    {
        $data = $this->getUserActiveSubscriptionData($user);
        return !empty($data['priceIds']);
    }

    /**
     * Gère customer.subscription.deleted
     *
     * @param StripeSubscription $subscription
     *
     * @param StripeInvoice $invoice
     * @return void
     */
    private function onInvoicePaymentFailed(StripeInvoice $invoice): void
    {
        $stripeSubId = $invoice->subscription;

        $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubId]);
        if (!$subscription) {
            $this->logger->warning("Abonnement local introuvable (échec paiement) pour #$stripeSubId.");
            return;
        }

        // Tu peux ici désactiver localement des accès
        $this->logger->warning("Paiement échoué pour l’abonnement #$stripeSubId (utilisateur {$subscription->getUser()->getEmail()})");
    }

    /**
     * Marque un abonnement comme expiré suite à une annulation Stripe
     *
     * @param StripeSubscription $stripeSub
     *
     * @param StripeSubscription $stripeSub
     * @return void
     */
    private function onSubscriptionCancelled(StripeSubscription $stripeSub): void
    {
        $stripeSubId = $stripeSub->id;

        $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSubId]);
        if (!$subscription) {
            $this->logger->warning("Abonnement local introuvable (annulé) pour #$stripeSubId.");
            return;
        }

        $subscription->setEnd(new DateTime());

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->logger->info("Abonnement #$stripeSubId annulé côté Stripe → marqué comme expiré.");
    }

    /**
     * Met à jour un abonnement local après un événement Stripe
     *
     * @param StripeSubscription $stripeSub
     * @return void
     */
    private function onSubscriptionUpdated(StripeSubscription $stripeSub): void
    {
        $subscription = $this->subscriptionRepository->findOneBy(['stripeSubscriptionId' => $stripeSub->id]);
        if (!$subscription) {
            $this->logger->warning("Abonnement local introuvable (updated) pour #{$stripeSub->id}.");
            return;
        }

        $subscription->setStart((new DateTime())->setTimestamp($stripeSub->current_period_start));
        $subscription->setEnd((new DateTime())->setTimestamp($stripeSub->current_period_end));

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->logger->info("Abonnement #{$stripeSub->id} mis à jour après événement Stripe.");
    }
}
