<?php

namespace App\Controller;

use App\Service\StripeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebhookController extends AbstractController
{
    public function __construct(
        private StripeService $stripe,
        private LoggerInterface $logger
    ) {}

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/stripe-webhook-subscription', name: 'stripe_webhook_subscription', methods: ['POST'])]
    public function stripeWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');
    
        try {
            $this->stripe->handleWebhook($payload, $signature);
        } catch (BadRequestHttpException $e) {
            return new Response('Invalid signature', 400);
        }
    
        return new Response('Webhook handled', 200);
    }
    
}