<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\LocaleSwitcher;

final readonly class UserLocaleListener
{

    public function __construct(
        private Security       $security,
        private LocaleSwitcher $localeSwitcher
    ) {}

    /**
     * @param RequestEvent $event
     * @return void
     */
    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->attributes->has('_locale')) {
            return;
        }

        $user = $this->security->getUser();
        if ($user instanceof User && $user->getLocale()) {
            $this->localeSwitcher->setLocale($user->getLocale());
            $request->setLocale($user->getLocale());
        }
    }
}
