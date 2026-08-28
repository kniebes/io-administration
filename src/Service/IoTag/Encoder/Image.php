<?php declare(strict_types=1);

namespace App\Service\IoTag\Encoder;

use App\Model\Image\ResponsiveImageTag\Parameter;
use App\Service\Image\ResponsiveImageTag\Interface\ResponsiveImageTagInterface;
use App\Service\IoTag\Encoder\Interface\IoTagEncoderInterface;
use Exception;
use Kniebes\IoCore\Repository\ImageRepository;
use \Kniebes\IoCore\Entity\Image as ImageEntity;

class Image implements IoTagEncoderInterface
{
    public function __construct(
        private readonly ImageRepository $imageRepository,
        private readonly ResponsiveImageTagInterface $responsiveImageTag,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function encode(string $string): string
    {
        preg_match_all('/<io:img[^>]+>/i', $string, $result);
        $img = [];
        foreach ($result[0] as $img_tag) {
            preg_match_all(
                '/(src|class|nolink|caption|alt|link|showexif)="([^"]*)"/i',
                $img_tag,
                $img[$img_tag]
            );
        }

        if (empty($img)) {
            return $string;
        }

        foreach ($img as $originalTag => $i) {
            if (!empty($i[1]) && !empty($i[2])) {
                $img[$originalTag]['attributes'] = [];
                $img[$originalTag]['originalTag'] = $originalTag;
                foreach ($i[1] as $key => $attributeName) {
                    $img[$originalTag]['attributes'][$attributeName] = $i[2][$key];
                }
                $imgTag = $this->createImageTag($img[$originalTag]['attributes']);
                $string = str_replace($originalTag, $imgTag, $string);
            }
        }

        return $string;
    }

    /**
     * @throws Exception
     */
    protected function createImageTag(array $attributes): string
    {
        $src = $attributes['src'];
        if (empty($src)) {
            return '';
        }
        $image = $this->imageRepository->findOneByUrl($src);

        if (empty($image)) {
            return '';
        }

        $hasExif = !empty($attributes['showexif']);
        $hasLink = empty($attributes['nolink']);
        $caption = $this->getCaption(attributes: $attributes, image: $image);
        $link = $attributes['link'] ?? '';
        $class = $attributes['class'] ?? '';

        return $this->responsiveImageTag->create(new Parameter(
            image: $image,
            baseVersion: '1024',
            hasLink: $hasLink,
            hasExif: $hasExif,
            caption: $caption,
            link: $link,
            figureClass: $class,
        ));
    }

    protected function getAlt(array $attributes, object $image): string
    {
        $alt = $attributes['alt'] ?? '';
        if (empty($alt) && !empty($image->custom_fields)) {
            $data = json_decode($image->custom_fields, true);
            if (is_array($data)) {
                $alt = $data['alt'] ?? '';
            }
        }

        return $alt;
    }

    protected function getCaption(array $attributes, ImageEntity $image): string
    {
        $attributeCaption = $attributes['caption'] ?? null;
        if (!empty($attributeCaption)) {
            return sprintf('%s', strip_tags($attributeCaption));
        }

        $customFields = $image->getCustomFields();
        if (!empty($customFields['caption'])) {
            return $customFields['caption'];
        }

        $titleCaption = $image->getTitle();
        if (empty($titleCaption)) {
            return '';
        }

        return sprintf('<strong>%s</strong>', $titleCaption);
    }
}
