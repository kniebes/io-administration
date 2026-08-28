<?php declare(strict_types=1);

namespace App\Model\Navigation;

use ArrayIterator;
use Doctrine\Common\Collections\ArrayCollection;

class NavigationItemCollection extends ArrayCollection
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add(mixed $element): void
    {
        if (!$element instanceof NavigationItem) {
            throw new \RuntimeException('Illegal element type: '.get_debug_type($element));
        }

        parent::add($element);
    }


    public function addElements(array $elements): void
    {
        foreach ($elements as $element) {
            $this->add($element);
        }
    }

    public function getSorted(): ArrayIterator
    {
        $iterator = $this->getIterator();
        $iterator->uasort(function (NavigationItem $a, NavigationItem$b) {
            return $a->getPosition() <=> $b->getPosition();
        });

        return $iterator;
    }
}
