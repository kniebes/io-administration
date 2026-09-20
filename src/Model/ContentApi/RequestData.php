<?php declare(strict_types=1);

namespace App\Model\ContentApi;

readonly class RequestData
{
    private RequestConfigData $config;
    public function __construct(
        private array $query,
        array $config,
    )
    {
        $this->config = new RequestConfigData($config);
    }

    public function getConfig(): RequestConfigData
    {
        return $this->config;
    }

    public function getQueryAsInt(string $key, ?int $default = null): ?int
    {
        if (isset($this->query[$key])) {
            return (int) $this->query[$key];
        }

        return $default;
    }

    public function getQueryAsString(string $key, ?string $default = null): ?string
    {
        if (isset($this->query[$key])) {
            return (string) $this->query[$key];
        }

        return $default;
    }

    public function getQueryAsFloat(string $key, ?float $default = null): ?float
    {
        if (isset($this->query[$key])) {
            return (float) $this->query[$key];
        }

        return $default;
    }

    public function getQueryAsBool(string $key, ?bool $default = null): ?bool
    {
        if (isset($this->query[$key])) {
            return (bool) $this->query[$key];
        }

        return $default;
    }
}
