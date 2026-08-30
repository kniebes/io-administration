<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\BlogPostPostSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BlogPostPostSaveEventSubscriber implements EventSubscriberInterface
{
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
            // @TODO Ping
        }
    }

    public function syndicate(BlogPostPostSaveEvent $event): void
    {
        // @TODO Syndicate
    }
}
