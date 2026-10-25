<?php declare(strict_types=1);

namespace App\Service\Image;

use App\Entity\Image;
use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

readonly class ImageFileRemover
{
    public function __construct(
        private ErrorLoggerInterface $errorLogger,
        #[Autowire(env: 'PHOTO_DOMAIN_PATH')] private string $photoDomainPath,
    ) {
    }

    /**
     * @return list<string>
     */
    public function collectPaths(Image $image): array
    {
        $paths = [$this->photoDomainPath . $image->getUrl()];

        foreach ($image->getVersions() as $imageVersion) {
            $paths[] = $this->photoDomainPath . $imageVersion->getUrl();
        }

        return $paths;
    }

    /**
     * @param list<string> $paths
     */
    public function removePaths(array $paths): void
    {
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
