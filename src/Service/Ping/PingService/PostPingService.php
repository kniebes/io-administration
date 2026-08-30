<?php declare(strict_types=1);

namespace App\Service\Ping\PingService;

use App\Model\Ping\PingConfiguration;
use App\Model\Ping\PingResult;
use App\Service\Ping\PingService\Interface\PingServiceInterface;

class PostPingService implements PingServiceInterface
{
    public function supports(PingConfiguration $pingConfiguration): bool
    {
        return $pingConfiguration->getPingServiceClass() === self::class;
    }

    public function ping(PingConfiguration $pingConfiguration): PingResult
    {
        $data = [
            'hub.mode' => 'publish',
            'hub.url' => $pingConfiguration->getFeedUrl(),
        ];
        $handle = curl_init($pingConfiguration->getPingServiceUrl());
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HTTPHEADER,  ['Content-Type: application/x-www-form-urlencoded']);
        $result = curl_exec($handle);
        curl_close($handle);
        if (!$result) {
            return new PingResult(pingService: $pingConfiguration->getPingServiceClass(), result: curl_error($handle));
        }

        return  new PingResult(pingService: $pingConfiguration->getPingServiceUrl(), result: $result);
    }
}
