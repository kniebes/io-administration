<?php declare(strict_types=1);

namespace App\Service\Image;

use App\Entity\Image;
use App\Entity\ImageVersion;

class ImageService
{
    public const array RESOLUTION = [100, 800, 1024, 2048];

    public function calcVersionUrl(Image $image, string $version = null): string
    {
        $domain = $image->getHost();
        if (empty($version)) {
            return sprintf('https://%s%s', $domain, $image->getUrl());
        }

        if (!in_array(intval($version), self::RESOLUTION)) {
            throw new \Exception('Invalid version '.$version);
        }

        $path = $image->getVersions()[$version]  ?? null;
        if (empty($path)) {
            return sprintf('https://%s%s', $domain, $image->getUrl());
        }

        return sprintf('https://%s%s', $domain, $path);
    }

    public function getPreviewUrlWithWidth(Image $imageEntity, ?int $width = 500): string
    {
        $distance = null;
        $selectedVersion = null;
        foreach ($imageEntity->getVersions() as $version) {
            $currentDistance = abs($width - $version->getWidth());
            if (is_null($distance) || $currentDistance < $distance) {
                $distance = $currentDistance;
                $selectedVersion = $version;
            }
        }

        if (empty($selectedVersion)) {
            return $this->generateImageUrl(host: $imageEntity->getHost(), url: $imageEntity->getUrl());
        }

        return $this->generateImageUrl(host: $imageEntity->getHost(), url: $selectedVersion->getUrl());
    }

    private function generateImageUrl(string $host, string $url): string
    {
        return sprintf('%s%s', $host, $url);
    }
}
