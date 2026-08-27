<?php declare(strict_types=1);

namespace App\Service\IoTag\Encoder;

use App\Service\ErrorLogger\Interface\ErrorLoggerInterface;
use App\Service\Image\ImageService;
use App\Service\IoTag\Encoder\Interface\IoTagEncoderInterface;
use Doctrine\Common\Collections\Collection;
use Exception;
use Kniebes\IoCore\Entity\Image;
use Kniebes\IoCore\Repository\ImageRepository;

class Gallery implements IoTagEncoderInterface
{
    public function __construct(
        private readonly ImageRepository $imageRepository,
        private readonly ImageService $imageService,
        private readonly ErrorLoggerInterface $errorLogger,
    )
    {
    }

    public function encode(string $string): string
    {
        preg_match_all('/<io:gallery[^>]+>/i', $string, $result);
        $gallery = [];
        $aspectRatio = [];
        foreach ($result[0] as $galleryTag) {
            preg_match_all('/(id)="([^"]*)"/i', $galleryTag, $gallery[$galleryTag]);
            preg_match_all('/(aspect-ratio)="([^"]*)"/i', $galleryTag, $aspectRatio[$galleryTag]);
        }

        if (empty($gallery)) {
            return $string;
        }

        foreach ($gallery as $galleryTag => $g) {
            try {
                $aspectRatio = isset($aspectRatio[$galleryTag][2][0]) ? (string) $aspectRatio[$galleryTag][2][0] : null;
                $htmlGallery = $this->generateHtmlGallery(gallery: $g, aspectRatio: $aspectRatio);
                if (!empty($htmlGallery)) {
                    $string = str_replace($galleryTag, $htmlGallery, $string);
                }
            } catch (Exception $e) {
                $this->errorLogger->log(
                    subject: 'Error generating image gallery',
                    message: 'Error generating image gallery: '.$e->getMessage(),
                    type: 'error'
                );
            }
        }

        return $string;
    }

    /**
     * @throws Exception
     */
    protected function generateHtmlGallery(array $gallery, ?string $aspectRatio = null): ?string
    {
        $attributes = [];
        foreach ($gallery[1] as $key => $attributeNames) {
            $attributes[$attributeNames] = $gallery[2][$key] ?? '';
        }

        $idList = explode(',', ($attributes['id'] ?? ''));
        array_walk($idList, function (&$value) {
            $value = intval($value);
        });
        /** @var Collection<Image> $images */
        $images = $this->imageRepository->findBy(['id' => $idList]);
        $imageTags = [];
        foreach ($images as $image) {
            $alt = $image->getAltText();
            if (empty($alt)) {
                $alt = trim(strip_tags($image->getDescriptionEncoded() ?? ''));
            }
            $linkUrl = $this->imageService->calcVersionUrl($image);
            $imgUrl = $this->imageService->calcVersionUrl($image, '800');
            $inlineStyle = '';
            if (!empty($aspectRatio)) {
                $inlineStyle = sprintf(' style="aspect-ratio: %s"', $aspectRatio);
            }

            $imageTags[] = sprintf(
                '<li><figure class="io-gallery-image"%s><a href="%s" aria-label="Bild in größerer Version anzeigen"><img src="%s" alt="%s"/></a></figure></li>',
                $inlineStyle,
                $linkUrl,
                $imgUrl,
                $alt
            );
        }

        if (!empty($imageTags)) {
            $imageCount = count($imageTags);
            $columnCountClass = match (true) {
                $imageCount === 3,  $imageCount > 4 => ' cols-3',
                default => ''
            };
            return '<ul class="io-gallery'.$columnCountClass.'">'.implode('', $imageTags).'</ul>';
        }

        return null;
    }

}
