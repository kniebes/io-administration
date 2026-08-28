<?php declare(strict_types=1);

namespace App\Service\Image;

use App\Entity\Image;

class ImageService
{
    public const array RESOLUTION = [100, 800, 1024, 2048];

    public function calcVersionUrl(Image $image, string $version = null): string
    {
        $domain = $image->getDomain();
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
}
