<?php declare(strict_types=1);

namespace App\Service\Image;

use App\Entity\BlogPostImageMapping;
use App\Entity\Image;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

readonly class ImageRemover
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ErrorLoggerInterface $errorLogger,
        #[Autowire(env: 'PHOTO_DOMAIN_PATH')] private string $photoDomainPath,
    ) {
    }

    public function remove(Image $image): void
    {
        $paths = [$this->photoDomainPath . $image->getUrl()];

        foreach ($image->getVersions() as $imageVersion) {
            $paths[] = $this->photoDomainPath . $imageVersion->getUrl();
        }

        $this->entityManager->wrapInTransaction(function () use ($image): void {
            $this->entityManager->createQueryBuilder()
                ->delete(BlogPostImageMapping::class, 'mapping')
                ->where('mapping.image = :image')
                ->setParameter('image', $image)
                ->getQuery()
                ->execute();

            $this->entityManager->remove($image);
        });

        foreach ($paths as $path) {
            try {
                if (is_file($path)) {
                    unlink($path);
                }
            } catch (Throwable $throwable) {
                $this->errorLogger->log($throwable);
            }
        }
    }
}
