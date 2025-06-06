<?php

namespace App\Controller;

use App\Repository\TradingSignalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TradingSignalHistoryController extends AbstractController
{
    public function __construct(
        private TradingSignalRepository $tradingSignalRepository
    ) {}

    #[Route('/trading/signal/history', name: 'app_trading_signal_history')]
    public function index(): Response
    {
        $signals = $this->tradingSignalRepository->findBy(
            ['status' => false],
            ['createdAt' => 'DESC']
        );

        $symbols = array_unique(array_map(fn($s) => $s->getSymbol(), $signals));
        sort($symbols);

        return $this->render('trading_signal_history/index.html.twig', [
            'signalsHistory' => $signals,
            'symbols' => $symbols,
        ]);
    }
}
