<?php declare(strict_types=1);

namespace App\Service\Image\ResponsiveImageTag\Interface;

use App\Model\Image\ResponsiveImageTag\Parameter;

interface ResponsiveImageTagInterface
{
    public function create(Parameter $parameter): string;
}
