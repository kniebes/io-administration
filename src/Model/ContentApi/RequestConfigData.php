<?php declare(strict_types=1);

namespace App\Model\ContentApi;

class RequestConfigData
{
    private string $imageContentWidth = '1024px';
    public function __construct(array $config)
    {
        if (isset($config['image_content_width'])) {
            $this->imageContentWidth = (string) $config['image_content_width'];
        }
    }

    public function getImageContentWidth(): string
    {
        return $this->imageContentWidth;
    }
}
