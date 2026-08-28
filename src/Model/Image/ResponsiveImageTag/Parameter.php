<?php declare(strict_types=1);

namespace App\Model\Image\ResponsiveImageTag;

use App\Entity\Image;

class Parameter
{
    public function __construct(
        private readonly Image $image,
        private readonly string $baseVersion = '1024',
        private readonly bool $hasLink = true,
        private readonly bool $hasExif = true,
        private readonly string $caption = '',
        private readonly ?string $link = null,
        private readonly ?string $figureClass = null,

    )
    {
    }

    public function getImage(): Image
    {
        return $this->image;
    }

    public function getBaseVersion(): string
    {
        return $this->baseVersion;
    }

    public function isHasLink(): bool
    {
        return $this->hasLink;
    }

    public function isHasExif(): bool
    {
        return $this->hasExif;
    }

    public function getCaption(): string
    {
        return $this->caption;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getFigureClass(): ?string
    {
        return $this->figureClass;
    }

}
