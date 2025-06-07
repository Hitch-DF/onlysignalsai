<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class LocaleSubscriber
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack,
        private LocaleSwitcher $localeSwitcher,
        private string $defaultLocale = 'en'
    ) {}

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Priorité : paramètre route _locale
        if ($request->attributes->has('_locale')) {
            $locale = $request->attributes->get('_locale');
        }
        // Puis : utilisateur connecté
        elseif (($user = $this->security->getUser()) instanceof User && $user->getLocale()) {
            $locale = $user->getLocale();
        }
        // Puis : locale en session
        elseif ($this->requestStack->getSession()->has('_locale')) {
            $locale = $this->requestStack->getSession()->get('_locale');
        }
        // Sinon : locale par défaut
        else {
            $locale = $this->defaultLocale;
        }

        $request->setLocale($locale);
        $this->localeSwitcher->setLocale($locale);
    }
}
