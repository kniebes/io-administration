<?php declare(strict_types=1);

namespace App\Service\Ping\PingService\Interface;

use App\Model\Ping\PingConfiguration;
use App\Model\Ping\PingResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.ping_service')]
interface PingServiceInterface
{
    public function supports(PingConfiguration $pingConfiguration): bool;
    public function ping(PingConfiguration $pingConfiguration): PingResult;
}
