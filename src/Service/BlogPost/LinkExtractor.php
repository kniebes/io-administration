<?php declare(strict_types=1);

namespace App\Service\BlogPost;

class LinkExtractor
{
    public function extract(string $html): array
    {
        preg_match_all('~<a[^>]+href="(https?://[^"]+)"~i', $html, $matches);

        return array_values(array_unique($matches[1]));
    }
}
