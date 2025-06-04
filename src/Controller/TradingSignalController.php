<?php

namespace App\Controller;

use App\Entity\TradingSignal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TradingSignalController extends AbstractController
{

    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('/simulate-signal', name: 'simulate_signal')]
    public function simulate(): Response
    {
        $assets = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT'];
        $categories = ['Crypto', 'Forex'];
        $types = TradingSignal::ALLOWED_TYPES;

        $signal = new TradingSignal();
        $signal
            ->setSymbol($assets[array_rand($assets)])
            ->setPrice(mt_rand(30000, 70000) + mt_rand(0, 99) / 100)
            ->setCreatedAt(new \DateTime())
            ->setSignalType($types[array_rand($types)])
            ->setCategory($categories[array_rand($categories)])
            ->setStatus((bool) random_int(0, 1));

        $this->entityManager->persist($signal);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_home');
    }
}
