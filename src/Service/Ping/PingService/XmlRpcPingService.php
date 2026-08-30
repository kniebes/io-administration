<?php declare(strict_types=1);

namespace App\Service\Ping\PingService;

use App\Model\Ping\PingConfiguration;
use App\Model\Ping\PingResult;
use App\Service\Ping\PingService\Interface\PingServiceInterface;

class XmlRpcPingService implements PingServiceInterface
{
    private string $xml = <<< XML
<?xml version="1.0" encoding="iso-8859-1"?><methodCall><methodName>weblogUpdates.extendedPing</methodName><params><param><value>Journal und Photoblog von Markus Kniebes</value></param><param><value>%s</value></param><param><value></value></param><param><value>%s</value></param></params></methodCall>
XML;

    public function supports(PingConfiguration $pingConfiguration): bool
    {
        return $pingConfiguration->getPingServiceClass() === self::class;
    }

    public function ping(PingConfiguration $pingConfiguration): PingResult
    {
        $data = sprintf($this->xml, $pingConfiguration->getSiteUrl(), $pingConfiguration->getFeedUrl());
        $command = sprintf('curl -X POST -v %s -H \'content-type: text/xml\' --data \'%s\'', $pingConfiguration->getPingServiceUrl(), $data);
        $result = exec($command);

        return new PingResult(pingService: $pingConfiguration->getPingServiceUrl(), result: $result);
    }
}
