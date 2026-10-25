<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ImagePreSaveEvent;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use App\Service\IoTag\IoTagEncoder;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

readonly class ImagePreSaveEventSubscriber implements EventSubscriberInterface
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
            ImagePreSaveEvent::class => [
                ['createEncodeFields', 0],
            ],
        ];
    }

    public function createEncodeFields(ImagePreSaveEvent $event): void
    {
        $image = $event->getImage();

        $image->setDescriptionEncoded($this->encode(text: $image->getDescription(), fallback: $image->getDescriptionEncoded()));

        foreach ($image->getTranslations() as $translation) {
            $translation->setDescriptionEncoded($this->encode(text: $translation->getDescription(), fallback: $translation->getDescriptionEncoded()));
        }
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
