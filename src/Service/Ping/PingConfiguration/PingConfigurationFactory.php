<?php declare(strict_types=1);

namespace App\Service\Ping\PingConfiguration;

use App\Entity\Blog;
use App\Model\Ping\PingConfiguration;
use App\Service\Ping\PingConfiguration\Interface\PingConfigurationFactoryInterface;
use App\Service\Ping\PingService\PostPingService;
use App\Service\Ping\PingService\XmlRpcPingService;

class PingConfigurationFactory implements PingConfigurationFactoryInterface
{
    public static function create(Blog $blog): array
    {
        $siteUrl = $blog->getBaseUrl();
        $feedUrl = $blog->getBaseUrl().$blog->getFeedUrl();
        $pingServices = $blog->getPingServices();
        $config = [];

        foreach ($pingServices as $pingService) {
            $config[] = match ($pingService) {
                'pubsubhubbub.appspot.com' => new PingConfiguration(pingServiceClass: PostPingService::class, pingServiceUrl: 'https://pubsubhubbub.appspot.com', siteUrl: $siteUrl, feedUrl: $feedUrl),
                'pubsubhubbub.superfeedr.com' => new PingConfiguration(pingServiceClass: PostPingService::class, pingServiceUrl: 'https://pubsubhubbub.superfeedr.com', siteUrl: $siteUrl, feedUrl: $feedUrl),
                'ping.blo.gs' => new PingConfiguration(pingServiceClass: XmlRpcPingService::class, pingServiceUrl: 'ping.blo.gs', siteUrl: $siteUrl, feedUrl: $feedUrl),
            };
        }

        return $config;
    }
}
