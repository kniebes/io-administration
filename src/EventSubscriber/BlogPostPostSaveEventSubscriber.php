<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\BlogPostPostSaveEvent;
use App\Message\PingMessage;
use App\Message\SyndicationMessage;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class BlogPostPostSaveEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ErrorLoggerInterface $errorLogger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BlogPostPostSaveEvent::class => [
                ['ping', 1000],
                ['syndicate', 900]
            ],
        ];
    }

    public function ping(BlogPostPostSaveEvent $event): void
    {
        if ($event->isFirstTimePublished()) {
            try {
                $this->messageBus->dispatch(new PingMessage($event->getBlogPost()->getId()));
            } catch (ExceptionInterface $e) {
                $this->errorLogger->log($e);
            }
        }
    }

    public function syndicate(BlogPostPostSaveEvent $event): void
    {
        try {
            $this->messageBus->dispatch(new SyndicationMessage($event->getBlogPost()->getId()));
        } catch (ExceptionInterface $e) {
            $this->errorLogger->log($e);
        }
    }
}
