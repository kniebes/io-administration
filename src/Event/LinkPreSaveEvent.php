<?php declare(strict_types=1);

namespace App\Event;

use App\Entity\Link;
use Symfony\Contracts\EventDispatcher\Event;

final class LinkPreSaveEvent extends Event
{
    public function __construct(private readonly Link $link)
    {
    }

    public function getLink(): Link
    {
        return $this->link;
    }
}
