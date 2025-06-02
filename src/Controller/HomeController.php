<?php

namespace App\Controller;

use App\Repository\SubscriptionRepository;
use App\Repository\TradingSignalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{

    public function __construct(
        private TradingSignalRepository $tradingSignalRepository,
        private SubscriptionRepository $subscriptionRepository
        )
    {
        
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();

        $hasActive = false;
        $signals = [];

        if ($user) {
            $hasActive = $this->subscriptionRepository->userHasActiveSubscription($user);
            if ($hasActive) {
                $signals = $this->tradingSignalRepository->findAll();
            }
        }

        return $this->render('home/index.html.twig', [
            'signals' => $signals,
            'hasActive' => $hasActive,
        ]);
    }
}
