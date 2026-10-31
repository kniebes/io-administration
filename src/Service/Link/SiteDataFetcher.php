<?php declare(strict_types=1);

namespace App\Service\Link;

use JsonException;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class SiteDataFetcher
{
    private const string API_URL = 'https://site-data.kniebes.io/';

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function fetch(string $url): array
    {
        try {
            $response = $this->httpClient->request(method: 'GET', url: self::API_URL, options: [
                'query' => ['url' => $url],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Site data request for ' . $url . ' returned status ' . $response->getStatusCode());
            }

            $data = json_decode($response->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (ExceptionInterface | JsonException $exception) {
            throw new RuntimeException(message: 'Could not load site data for ' . $url, previous: $exception);
        }

        if (!is_array($data)) {
            throw new RuntimeException('Invalid site data for ' . $url);
        }

        return is_array($data['meta'] ?? null) ? $data['meta'] : [];
    }
}
