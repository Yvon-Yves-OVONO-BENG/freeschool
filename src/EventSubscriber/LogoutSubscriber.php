<?php

namespace App\EventSubscriber;

use App\Repository\UserLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private UserLogRepository $userLogRepo
    ) {}

    public function onLogout(LogoutEvent $event)
    {
        $user = $event->getToken()?->getUser();
        if (!$user) return;

        // Cherche la dernière connexion non déconnectée
        $log = $this->userLogRepo->findOneBy([
            'user' => $user,
            'disconnectedAt' => null
        ], ['logedAt' => 'DESC']);

        if ($log) {
            $log->setDisconnectedAt(new \DateTime());
            $this->em->flush();
        }
    }

        public static function getSubscribedEvents(): array
        {
            return [
                LogoutEvent::class => 'onLogout',
            ];
        }
    }