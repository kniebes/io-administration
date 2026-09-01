<?php declare(strict_types=1);

namespace App\Service\BlogPost;

use App\Entity\BlogPost;
use App\Entity\BlogPostSlugArchive;
use App\Repository\BlogPostRepository;
use App\Repository\BlogPostSlugArchiveRepository;
use App\Service\BlogPost\Interface\SlugManagerInterface;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use Doctrine\DBAL\Connection;
use Throwable;

readonly class SlugManager implements SlugManagerInterface
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private BlogPostSlugArchiveRepository $blogPostSlugArchiveRepository,
        private Connection $connection,
        private ErrorLoggerInterface $errorLogger,
    )
    {
    }

    public function slugChange(string $newSlug, BlogPost $blogpost): void
    {
        if (empty($blogpost->getId())) {
            return;
        }

        try {
            $currentSlug = $this->findCurrentSlug($blogpost->getId());
            if ($currentSlug !== $newSlug) {
                $this->createSlugArchive($currentSlug, $blogpost);
            }
        } catch (Throwable $throwable) {
            $this->errorLogger->log(throwable: $throwable, type: ErrorLoggerInterface::TYPE_EMAIL);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function findCurrentSlug(int $blogpostId): string
    {
        $tableName = $this->blogPostRepository->getTableName();
        $sql = 'SELECT slug FROM `%s` WHERE id = :blogpost_id';
        $stmt = $this->connection->prepare(sprintf($sql, $tableName));
        $stmt->bindValue('blogpost_id', $blogpostId);

        return $stmt->executeQuery()->fetchOne();
    }

    private function createSlugArchive(string $slug, BlogPost $blogpost): void
    {
        if ($this->isSlugExists($slug)) {
            return;
        }

        $slugArchive = new BlogPostSlugArchive();
        $slugArchive->setSlug($slug);
        $slugArchive->setBlogPost($blogpost);
        $this->blogPostSlugArchiveRepository->save($slugArchive);
    }

    private function isSlugExists(string $slug): bool
    {
        return !empty($this->blogPostSlugArchiveRepository->findOneBy(['slug' => $slug]));
    }
}
