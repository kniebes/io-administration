<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\SavedBlogPostEvent;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use App\Service\IoTag\IoTagEncoder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Kniebes\IoCore\Entity\BlogPost;
use Kniebes\IoCore\Repository\BlogPostRepository;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnrichBlogPostEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly IoTagEncoder $ioTagEncoder,
        private readonly CommonMarkConverter $converter,
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly ErrorLoggerInterface $errorLogger,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SavedBlogPostEvent::class => [
                ['enrichContent', 0],
            ],
        ];
    }

    public function enrichContent(SavedBlogPostEvent $event): void
    {
        $blogPost = $event->getBlogPost();
        if (empty($blogPost->getId())) {
            throw new \RuntimeException('Blog post id not set');
        }

        $content = $blogPost->getContent();
        $contentEncoded = $summaryEncoded = null;
        if (!empty($content)) {
            try {
                $contentEncoded = $this->ioTagEncoder->encode($content);
                $contentEncoded = $this->converter->convert($contentEncoded)->getContent();
            } catch (CommonMarkException $e) {
                $this->errorLogger->log(
                    subject: 'enrichContent:content - '.$e->getMessage(),
                    message: $e->getTraceAsString()
                );
            }
        }

        $summary = $blogPost->getSummary();
        if (!empty($summary)) {
            try {
                $summaryEncoded = $this->ioTagEncoder->encode($summary);
                $summaryEncoded = $this->converter->convert($summaryEncoded)->getContent();
            } catch (CommonMarkException $e) {
                $this->errorLogger->log(
                    subject: 'enrichContent:summary - '.$e->getMessage(),
                    message: $e->getTraceAsString()
                );
            }
        }

        $this->save(blogPost: $blogPost, contentEncoded: $contentEncoded, summaryEncoded: $summaryEncoded);
    }

    private function save(BlogPost $blogPost, ?string $contentEncoded, ?string $summaryEncoded): void
    {
        $classMetadata = $this->entityManager->getClassMetadata(BlogPost::class);

        $this->connection->executeStatement(
            sprintf(
                'UPDATE %s SET %s = :contentEncoded, %s = :summaryEncoded WHERE %s = :id',
                $classMetadata->getTableName(),
                $classMetadata->getColumnName('contentEncoded'),
                $classMetadata->getColumnName('summaryEncoded'),
                $classMetadata->getColumnName('id'),
            ),
            [
                'contentEncoded' => $contentEncoded,
                'summaryEncoded' => $summaryEncoded,
                'id' => $blogPost->getId(),
            ],
        );
    }

}
