<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class IdleSessionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(param: 'app.session_idle_timeout')]
        private readonly int $idleTimeout,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 9]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $lastUsed = $session->getMetadataBag()->getLastUsed();

        if ($lastUsed > 0 && (time() - $lastUsed) > $this->idleTimeout) {
            $session->invalidate();
        }
    }
}
