<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\LocaleSwitcher;

class LocaleController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security               $security,
        private LocaleSwitcher $localeSwitcher
    ) {}

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    #[Route("/switch-locale", name: "switch_locale", methods: ["POST"])]
    public function changeLocale(Request $request): RedirectResponse
    {
        $newLocale = $request->request->get('locale');
        $validLocales = ['fr', 'en'];
        $locale = in_array($newLocale, $validLocales) ? $newLocale : 'en';

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            $request->getSession()->set('_locale', $locale);
        }
        if ($user instanceof User) {
            $user->setLocale($locale);
            $this->entityManager->flush();
        }



        $request->setLocale($locale);
        $this->localeSwitcher->setLocale($locale);

        return $this->redirect($request->headers->get('referer') ?? '/');
    }
}
