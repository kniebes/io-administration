<?php declare(strict_types=1);

namespace App\Twig;

use App\Entity\Image;
use App\Service\Image\ImageService;
use Twig\Attribute\AsTwigFunction;

readonly class ImagePreview
{
    public function __construct(
        private ImageService $imageService,
    )
    {
    }

    #[AsTwigFunction('getImagePreviewUrl')]
    public function getImagePreviewUrl(Image $image, ?int $width = 500): string
    {
        return $this->imageService->getPreviewUrlWithWidth(imageEntity: $image, width: $width);
    }
}
