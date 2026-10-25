<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\Image;
use Symfony\Contracts\EventDispatcher\Event;

final class ImagePreSaveEvent extends Event
{
    public function __construct(private readonly Image $image)
    {
    }

    public function getImage(): Image
    {
        return $this->image;
    }
}
