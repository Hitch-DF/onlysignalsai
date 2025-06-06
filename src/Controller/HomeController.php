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
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $user = $this->getUser();

        $hasActive = false;
        $signals = [];
        $assets = [];
        $categories = [];

        if ($user) {
            $hasActive = $this->subscriptionRepository->userHasActiveSubscription($user);
            if ($hasActive) {
                $signals = $this->tradingSignalRepository->findBy(
                    ['status' => true],
                    ['createdAt' => 'DESC'],
                );

                // Assets uniques
                $assets = array_unique(array_map(fn($s) => $s->getSymbol(), $signals));
                sort($assets);

                // Catégories uniques
                $categories = array_unique(array_map(fn($s) => $s->getCategory(), $signals));
                sort($categories);
            }
        }

        $signalsHistory = $this->tradingSignalRepository->findBy(
            ['status' => false],
            ['createdAt' => 'DESC'],
        );

        return $this->render('home/index.html.twig', [
            'signals' => $signals,
            'hasActive' => $hasActive,
            'assets' => $assets,
            'categories' => $categories,
            'signalsHistory' => $signalsHistory,
        ]);
    }
}
