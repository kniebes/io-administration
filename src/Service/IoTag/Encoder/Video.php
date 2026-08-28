<?php declare(strict_types=1);

namespace App\Service\IoTag\Encoder;

use App\Service\IoTag\Encoder\Interface\IoTagEncoderInterface;

class Video /*implements IoTagEncoderInterface*/
{
    public function encode(string $string): string
    {
        preg_match_all('/<io:video[^>]+>/i', $string, $result);
        $videoTags = [];
        foreach ($result[0] as $videoTag) {
            preg_match_all(
                '/(url)="([^"]*)"/i',
                $videoTag,
                $videoTags[$videoTag]
            );
        }

        if (empty($videoTags)) {
            return $string;
        }

        foreach ($videoTags as $videoTag => $attributes) {
            if (empty($attributes[1]) || empty($attributes[2])) {
                continue;
            }

            $videoTags[$videoTag]['attributes'] = [];
            $videoTags[$videoTag]['originalTag'] = $videoTag;
            foreach ($attributes[1] as $key => $name) {
                $videoTags[$videoTag]['attributes'][$name] = $attributes[2][$key];
            }
            $html = $this->createVideoTag($videoTags[$videoTag]['attributes']);
            $string = str_replace($videoTag, $html, $string);
        }

        return $string;
    }

    protected function createVideoTag(array $attributes): string
    {
        $url = $attributes['url'] ?? '';
        if (empty($url)) {
            return '';
        }

        $video = JournalVideoRepository::findByUrl($url);
        if (empty($video)) {
            return '';
        }

        $customFields = json_decode(($video->custom_fields ?? ''), true);
        $videoTagAttributes = $customFields['video-tag-attributes'] ?? '';

        return match ($video->video_domain) {
            'www.youtube.com' => call_user_func(function () use ($video) {
                $imageTag = sprintf('<img src="https://%s%s" style="aspect-ratio: %s" alt="%s">', $video->preview_domain, $video->preview_url, number_format(($video->preview_aspect_ratio ?? 3/2),2), $video->title);
                $ankerTag = sprintf('<a href="https://%s%s">%s</a>', $video->video_domain, $video->video_url, $imageTag);
                $figCaptionTag = sprintf('<figcaption>%s</figcaption>', $video->title);

                return sprintf('<figure class="io-video extern">%s%s<div class="play-button"></div></figure>', $ankerTag, $figCaptionTag);
            }),
            default => call_user_func(function () use ($video, $videoTagAttributes) {
                $sourceTag = sprintf('<source src="https://%s%s" type="%s">', $video->video_domain, $video->video_url, $video->mime_type);
                $annotation = sprintf('<p><a href="https://%s%s">Download</a></p>', $video->video_domain, $video->video_url);
                return sprintf('<figure class="io-video local"><video id="video-%d" %s style="aspect-ratio: %s">%s%s</video></figure>', $video->id, $videoTagAttributes, number_format(($video->video_aspect_ratio ?? 3/2), 2), $sourceTag, $annotation);
            }),
        };
    }

}
