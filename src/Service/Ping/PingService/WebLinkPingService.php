<?php declare(strict_types=1);

namespace App\Service\Ping\PingService;

use App\Model\Ping\PingConfiguration;
use App\Model\Ping\PingResult;
use App\Service\Ping\PingService\Interface\PingServiceInterface;

class WebLinkPingService implements PingServiceInterface
{
    public function supports(PingConfiguration $pingConfiguration): bool
    {
        return $pingConfiguration->getPingServiceClass() === self::class;
    }

    public function ping(PingConfiguration $pingConfiguration): PingResult
    {
        $result = file_get_contents($pingConfiguration->getPingServiceUrl());

        return new PingResult(pingService: $pingConfiguration->getPingServiceUrl(), result: $result);
    }
}
