<?php declare(strict_types=1);

namespace App\Service\Ping\PingConfiguration\Interface;

use App\Entity\Blog;

interface PingConfigurationFactoryInterface
{
    public static function create(Blog $blog): array;
}
