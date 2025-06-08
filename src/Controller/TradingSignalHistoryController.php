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
        $signalsHistory = $this->tradingSignalRepository->findHistoricalSignals();

        $assetsHistory = array_unique(array_map(fn($s) => $s->getSymbol(), $signalsHistory));
        sort($assetsHistory);

        $categoriesHistory = array_unique(array_map(fn($s) => $s->getCategory(), $signalsHistory));
        sort($categoriesHistory);

        return $this->render('trading_signal_history/index.html.twig', [
            'signalsHistory' => $signalsHistory,
            'signalsHistory' => $signalsHistory,
            'assetsHistory' => $assetsHistory,
            'categoriesHistory' => $categoriesHistory
        ]);
    }
}
