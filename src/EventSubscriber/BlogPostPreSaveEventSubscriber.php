<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\BlogPostStatus;
use App\Event\BlogPostPreSaveEvent;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use App\Service\IoTag\IoTagEncoder;
use League\CommonMark\CommonMarkConverter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

readonly class BlogPostPreSaveEventSubscriber implements EventSubscriberInterface
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
            BlogPostPreSaveEvent::class => [
                ['enrichContent', 0],
                ['handlePublishedState', 10],
            ],
        ];
    }

    public function handlePublishedState(BlogPostPreSaveEvent $event): void
    {
        $blogPost = $event->getBlogPost();

        if ($blogPost->getStatus() !== BlogPostStatus::Published) {
            return;
        }

        if (empty($blogPost->getPublishedDate())) {
            $blogPost->setPublishedDate(new \DateTimeImmutable());
            $event->setIsFirstTimePublished(true);
        }
    }

    public function enrichContent(BlogPostPreSaveEvent $event): void
    {
        $blogPost = $event->getBlogPost();

        $content = $blogPost->getContent();
        if (!empty($content)) {
            try {
                $contentEncoded = $this->ioTagEncoder->encode($content);
                $contentEncoded = $this->converter->convert($contentEncoded)->getContent();
                $blogPost->setContentEncoded($contentEncoded);
            } catch (Throwable $throwable) {
                $this->errorLogger->log(
                    subject: 'enrichContent:content - '.$throwable->getMessage(),
                    message: $throwable->getTraceAsString()
                );
            }
        }

        $summary = $blogPost->getSummary();
        if (!empty($summary)) {
            try {
                $summaryEncoded = $this->ioTagEncoder->encode($summary);
                $summaryEncoded = $this->converter->convert($summaryEncoded)->getContent();
                $blogPost->setSummaryEncoded($summaryEncoded);
            } catch (Throwable $throwable) {
                $this->errorLogger->log(
                    subject: 'enrichContent:summary - '.$throwable->getMessage(),
                    message: $throwable->getTraceAsString()
                );
            }
        }
    }
}
