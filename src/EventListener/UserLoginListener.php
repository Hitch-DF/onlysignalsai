<?php

namespace App\EventListener;

use App\Entity\UserLoginHistory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class UserLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {}

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        $request = $this->requestStack->getCurrentRequest();
        $ipAddress = $request?->getClientIp();

        if ($user instanceof UserInterface) {
            $loginHistory = new UserLoginHistory();
            $loginHistory->setUser($user);
            $loginHistory->setLoginAt(new DateTime());
            $loginHistory->setIpAddress($ipAddress);

            $this->entityManager->persist($loginHistory);
            $this->entityManager->flush();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'security.interactive_login' => 'onSecurityInteractiveLogin',
        ];
    }
}
