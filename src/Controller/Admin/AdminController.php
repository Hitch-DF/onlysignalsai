<?php

namespace App\Controller\Admin;

use App\Entity\TradingSignal;
use App\Form\CloseSignalForm;
use App\Form\TradingSignalForm;
use App\Form\UserForm;
use App\Repository\TradingSignalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private TradingSignalRepository $tradingSignalRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'app_dashboard', methods: ['GET', 'POST'])]
    public function dashboard(Request $request): Response
    {
        // Récupération des signaux
        $signals = $this->tradingSignalRepository->findActiveSignals();
        $signalsHistory = $this->tradingSignalRepository->findHistoricalSignals();
        $users = $this->userRepository->findAll();

        // Nouveau signal historique
        $newSignal = new TradingSignal();
        $newSignalForm = $this->createForm(TradingSignalForm::class, $newSignal);
        $newSignalForm->handleRequest($request);

        if ($newSignalForm->isSubmitted() && $newSignalForm->isValid()) {
            $newSignal->setStatus(false); // Historique directement
            $this->entityManager->persist($newSignal);
            $this->entityManager->flush();

            $this->addFlash('success', 'Signal ajouté avec succès.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Édition des signaux historiques
        $editForms = [];
        foreach ($signalsHistory as $signal) {
            $form = $this->createForm(TradingSignalForm::class, $signal);
            $editForms[$signal->getId()] = $form->createView();
        }

        // Clôture des signaux actifs – avec clonage pour éviter le bug
        $closeForms = [];
        foreach ($signals as $signal) {
            $form = $this->createForm(CloseSignalForm::class, $signal, [
                'action' => $this->generateUrl('app_admin_trading_signal_close', ['id' => $signal->getId()]),
                'method' => 'POST',
            ]);
            $closeForms[$signal->getId()] = $form->createView();
        }

        // Formulaires d’édition utilisateurs
        $userForms = [];
        foreach ($users as $user) {
            $form = $this->createForm(UserForm::class, $user, [
                'action' => $this->generateUrl('app_admin_user_edit', ['id' => $user->getId()]),
                'method' => 'POST',
            ]);
            $userForms[$user->getId()] = $form->createView();
        }



        return $this->render('admin/index.html.twig', [
            'signals' => $signals,
            'signalsHistory' => $signalsHistory,
            'users' => $users,
            'signalForm' => $newSignalForm->createView(),
            'editForms' => $editForms,
            'closeForms' => $closeForms,
            'userForms' => $userForms,
        ]);
    }
}
