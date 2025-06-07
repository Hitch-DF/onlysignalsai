<?php

namespace App\Controller\Admin;

use App\Entity\TradingSignal;
use App\Form\CloseSignalForm;
use App\Form\TradingSignalForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trading/signal')]
final class TradingSignalController extends AbstractController
{


    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('/{id}/edit', name: 'app_admin_trading_signal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TradingSignal $tradingSignal): Response
    {
        $form = $this->createForm(TradingSignalForm::class, $tradingSignal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $screenshot = $form->get('screenshot')->getData();
            if ($screenshot) {
                $filename = uniqid() . '.' . $screenshot->guessExtension();
                $screenshot->move($this->getParameter('upload_directory'), $filename);
                $tradingSignal->setScreenshot($filename);
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'Signal modifié avec succès.');
            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/trading_signal/edit.html.twig', [
            'trading_signal' => $tradingSignal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_trading_signal_delete', methods: ['POST'])]
    public function delete(Request $request, TradingSignal $tradingSignal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tradingSignal->getId(), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($tradingSignal);
            $this->entityManager->flush();
            $this->addFlash('success', 'Signal supprimé.');
        }

        return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/signal/{id}/close', name: 'app_admin_trading_signal_close', methods: ['POST'])]
    public function close(Request $request, TradingSignal $signal): Response
    {
        $form = $this->createForm(CloseSignalForm::class, $signal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $screenshot = $form->get('screenshot')->getData();
            if ($screenshot) {
                $filename = uniqid() . '.' . $screenshot->guessExtension();
                $screenshot->move($this->getParameter('upload_directory'), $filename);
                $signal->setScreenshot($filename);
            }
            
            $signal->setStatus(false);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le signal a été clôturé.');
        }

        return $this->redirectToRoute('app_dashboard');
    }
}
