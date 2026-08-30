<?php declare(strict_types=1);

namespace App\Model\Ping;

readonly class PingConfiguration
{
    public function __construct(
        private string $pingServiceClass,
        private string $pingServiceUrl,
        private string $siteUrl,
        private string $feedUrl,
    )
    {
    }

    public function getPingServiceClass(): string
    {
        return $this->pingServiceClass;
    }

    public function getPingServiceUrl(): string
    {
        return $this->pingServiceUrl;
    }

    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    public function getFeedUrl(): string
    {
        return $this->feedUrl;
    }
}
