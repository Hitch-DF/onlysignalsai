<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileForm;
use App\Repository\UserLoginHistoryRepository;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface      $entityManager,
        private TranslatorInterface         $translator,
        private UserPasswordHasherInterface $passwordHasher,
        private UserLoginHistoryRepository $userLoginHistoryRepository,
        private StripeService $stripe,
    ) {}

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request): Response
    {
        $user = $this->entityManager->getRepository(User::class)->find($this->getUser());
        $lastConnection = $this->userLoginHistoryRepository->findLastLoginDateForUser($user);
        $form = $this->createForm(ProfileForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if (!empty($plainPassword)) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $plainPassword)
                );
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('Modifications effectuées avec succès'));
        }

        if ($user) {
            $subData = $this->stripe->getUserActiveSubscriptionData($user);
            $activeSubId = $subData['subId'];
            $subDetails = $this->stripe->getSubscriptionDetails($activeSubId);
            if ($subDetails) {
                $price = $subDetails['plan']['amount'] / 100;
                $interval = $subDetails['plan']['interval'];
                $currency = $subDetails['plan']['currency'];
                $details = [
                    'price' => $price,
                    'interval' => $interval,
                    'currency' => $currency
                ];
            }
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form,
            'lastConnection' => $lastConnection,
            'details' => $details ?? []
        ]);
    }
}
