<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\LinkPreSaveEvent;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use App\Service\IoTag\IoTagEncoder;
use DateTimeImmutable;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

readonly class LinkPreSaveEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IoTagEncoder $ioTagEncoder,
        private CommonMarkConverter $converter,
        private ErrorLoggerInterface $errorLogger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LinkPreSaveEvent::class => [
                ['setPublishedDate', 10],
                ['createEncodeFields', 0],
            ],
        ];
    }

    public function setPublishedDate(LinkPreSaveEvent $event): void
    {
        $link = $event->getLink();

        if ($link->getPublishedDate() === null) {
            $link->setPublishedDate(new DateTimeImmutable());
        }
    }

    public function createEncodeFields(LinkPreSaveEvent $event): void
    {
        $link = $event->getLink();

        $link->setDescriptionEncoded($this->encode(text: $link->getDescription(), fallback: $link->getDescriptionEncoded()));
    }

    private function encode(?string $text, ?string $fallback): ?string
    {
        if (empty($text)) {
            return null;
        }

        try {
            $encoded = $this->ioTagEncoder->encode($text);

            return $this->converter->convert($encoded)->getContent();
        } catch (Throwable $throwable) {
            $this->errorLogger->log($throwable);
        }

        return $fallback;
    }
}
