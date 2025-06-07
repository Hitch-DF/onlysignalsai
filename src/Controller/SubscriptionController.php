<?php

namespace App\Controller;

use App\Repository\SubscriptionRepository;
use App\Service\StripeService;
use App\Service\TemplateService;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class SubscriptionController extends AbstractController
{
    public function __construct(
        private StripeService $stripe,
        private SubscriptionRepository $subscriptionRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Retourne la page des plans d'abonnement
     *
     * @return Response
     */
    #[Route('/plans', name: 'subscription_plans', methods: ['GET'])]
    public function showPlans(): Response
    {
        $plans = $this->stripe->getActivePlans();

        if ($user = $this->getUser()) {
            $subData = $this->stripe->getUserActiveSubscriptionData($user);
            $subscriptionPriceIds = $subData['priceIds'];
            $subscriptionEndDates = $subData['endDates'];
        } else {
            $subscriptionPriceIds = [];
            $subscriptionEndDates = [];
        }

        return $this->render('plans/index.html.twig', [
            'plans'                => $plans,
            'subscriptionPriceIds' => $subscriptionPriceIds,
            'subscriptionEndDates' => $subscriptionEndDates,
        ]);
    }


    #[Route('/create-subscription-session', name: 'create_subscription_session', methods: ['POST'])]
    public function createSession(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $priceId = $data['priceId'] ?? null;
        $mode = $data['mode'] ?? 'subscription';

        if (!$priceId) {
            return $this->json(['error' => 'PriceId manquant'], 400);
        }

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté.'], 401);
        }

        try {
            $domain = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);

            if ($mode === 'lifetime') {
                $res = $this->stripe->createLifetimeAccessSession(
                    $priceId,
                    $domain . 'success?session_id={CHECKOUT_SESSION_ID}',
                    $domain . 'cancel',
                    $user->getEmail()
                );
            } else {
                $res = $this->stripe->createSubscriptionSession(
                    $priceId,
                    $domain . 'success?session_id={CHECKOUT_SESSION_ID}',
                    $domain . 'cancel',
                    $user->getEmail()
                );
            }

            return $this->json([
                'sessionId' => $res['id'],
                'publicKey' => $res['publicKey'],
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la création de la session : " . $e->getMessage());
            return $this->json(['error' => 'Une erreur est survenue lors de la création de la session.'], 500);
        }
    }


    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/success', name: 'checkout_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        $sessionId = $request->query->get('session_id');

        if (!$sessionId) {
            $this->addFlash('error', 'Session manquante');
            return $this->redirectToRoute('app_home');
        }

        try {
            // Récupère la session avec détails des articles
            $session = $this->stripe->getSessionDetails($sessionId);
            $lineItem = $session->line_items->data[0] ?? null;

            if (!$lineItem) {
                throw new \Exception('Aucun line item trouvé dans la session.');
            }

            $price = $lineItem->price;
            $product = $price->product;

            $isLifetime = $price->type === 'one_time';

            $subscription = null;
            if (!$isLifetime && $session->subscription) {
                $subscription = $this->stripe->getSubscriptionDetails($session->subscription);
            }

            return $this->render('plans/success.html.twig', [
                'session'      => $session,
                'lineItem'     => $lineItem,
                'price'        => $price,
                'product'      => $product,
                'subscription' => $subscription,
                'isLifetime'   => $isLifetime,
            ]);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des détails de la session #$sessionId : " . $e->getMessage());

            $this->addFlash('error', 'Impossible de récupérer les détails de la session. Veuillez contacter le support.');
            return $this->redirectToRoute('app_home');
        }
    }


    /**
     * @return Response
     */
    #[Route('/cancel', name: 'checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        return $this->render('plans/cancel.html.twig');
    }

    #[Route('/create-lifetime-session', name: 'create_lifetime_session', methods: ['POST'])]
    public function createLifetimeSession(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $priceId = $data['priceId'] ?? null;

        if (!$priceId) {
            return $this->json(['error' => 'PriceId manquant'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], 401);
        }

        try {
            $domain = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $res = $this->stripe->createLifetimeAccessSession(
                $priceId,
                $domain . 'success?session_id={CHECKOUT_SESSION_ID}',
                $domain . 'cancel',
                $user->getEmail()
            );

            return $this->json([
                'sessionId' => $res['id'],
                'publicKey' => $res['publicKey'],
            ]);
        } catch (Exception $e) {
            $this->logger->error("Erreur session lifetime : " . $e->getMessage());
            return $this->json(['error' => 'Erreur lors de la création de la session.'], 500);
        }
    }
}
