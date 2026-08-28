<?php declare(strict_types=1);

namespace App\Service\IoTag\Encoder;

use App\Service\IoTag\Encoder\Interface\IoTagEncoderInterface;

class Aside implements IoTagEncoderInterface
{
    public function encode(string $string): string
    {
        preg_match_all('/<io:aside[^>]+>/i', $string, $result);
        $asides = [];
        foreach ($result[0] as $asideTag) {
            preg_match_all('/(href|title|text|quote|description|tldr)="([^"]*)"/i', $asideTag, $asides[$asideTag]);
        }

        if (empty($asides)) {
            return $string;
        }

        foreach ($asides as $asideTag => $g) {
            $attributes = [];
            foreach ($g[1] as $key => $attributeNames) {
                $attributes[$attributeNames] = $g[2][$key] ?? '';
            }
            $headlineLevel = $attributeNames['headline'] ?? 'h3';
            $htmlAside = [];
            $openTag = '<section>';
            $closeTag = '</section>';

            switch (!empty($attributes['href'])) {
                case true: // create section: Link
                    $openTag = '<section class="link">';
                    $closeTag = '</section>';
                    $htmlAside[] = match (empty($attributes['title'])) {
                        true => sprintf(
                            '<%s><a href="%s">%s</a></%s>',
                            $headlineLevel,
                            $attributes['href'],
                            $attributes['href'],
                            $headlineLevel
                        ),
                        false => sprintf(
                            '<%s><a href="%s">%s</a></%s>',
                            $headlineLevel,
                            $attributes['href'],
                            $attributes['title'],
                            $headlineLevel
                        )
                    };
                    if (!empty($attributes['quote'])) {
                        $htmlAside[] = sprintf('<blockquote><p>%s</p></blockquote>', $attributes['quote']);
                    }
                    break;

                case false: // craete aside: no Link
                    $openTag = '<aside>';
                    $closeTag = '</aside>';
                    if (!empty($attributes['title'])) {
                        $htmlAside[] = sprintf('<%s>%s</%s>', $headlineLevel, $attributes['title'], $headlineLevel);
                    }
                    if (!empty($attributes['quote'])) {
                        $htmlAside[] = sprintf('<blockquote><p>%s</p></blockquote>', $attributes['quote']);
                    }
                    break;
            }

            if (!empty($attributes['description'])) {
                $htmlAside[] = sprintf('<p><q>%s</q></p>', $attributes['description']);
            }
            if (!empty($attributes['text'])) {
                $htmlAside[] = sprintf('<p>%s</p>', $attributes['text']);
            }
            // skip previous content and make it an TLDR;
            if (!empty($attributes['tldr'])) {
                $openTag = '<aside>';
                $closeTag = '</aside>';
                $htmlAside = [sprintf('<p><strong>tl;dr</strong> %s</p>', $attributes['tldr'])];
            }

            if (!empty($htmlAside)) {
                $htmlAside = sprintf('%s%s%s', $openTag, implode(' ', $htmlAside), $closeTag);
                $string = str_replace($asideTag, $htmlAside, $string);
            }
        }

        return $string;
    }

}
