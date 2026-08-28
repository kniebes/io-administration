<?php

namespace App\Service\Image\ResponsiveImageTag;

use App\Model\Image\ResponsiveImageTag\Parameter;
use App\Service\Image\ResponsiveImageTag\Interface\ResponsiveImageTagInterface;
use Exception;
use Kniebes\IoCore\Entity\Image;
use Throwable;

class ResponsiveImageTag implements ResponsiveImageTagInterface
{
    /**
     * @throws Exception
     */
    public function create(Parameter $parameter): string
    {
        $customFields = $parameter->getImage()->getCustomFields();

        $hasVersions = $parameter->getImage()->getVersions();
        $baseImageUrl = $this->getVersionUrl($parameter->getImage(), $hasVersions ? $parameter->getBaseVersion() : null);

        ['width' => $width, 'height' => $height] = $this->getImageSize($parameter->getImage(), $parameter->getBaseVersion());
        $srcset = [];
        if ($hasVersions) {
            foreach ($this->getResolution() as $res) {
                $srcset[] = sprintf('%s %dw', $this->getVersionUrl($parameter->getImage(), (string)$res), $res);
            }
        }

        $style = '';
        if (!empty($parameter->getImage()->getAspectRatio())) {
            $style = sprintf(' style="aspect-ratio: %s;"', number_format($parameter->getImage()->getAspectRatio(),1));
        }
        $srcsetAttr = '';
        if (!empty($srcset)) {
            $sizes = sprintf('(max-width: %dpx) 100vw, %dpx', $parameter->getBaseVersion(), $parameter->getBaseVersion());
            $srcsetAttr = sprintf(' srcset="%s" sizes="%s"', implode(', ', $srcset), $sizes);
        }
        $tag = sprintf(
            '<img loading="lazy" src="%s"%s%s%s alt="%s"%s>',
            $baseImageUrl,
            (!is_null($width) ? ' width="'.$width.'"' : ''),
            (!is_null($height) ? ' height="'.$height.'"' : ''),
            $srcsetAttr,
            $parameter->getImage()->getAltText(),
            $style
        );

        if ($parameter->isHasLink()) {
            $link = $parameter->getLink();
            if (empty($link)) {
                $link = $this->getVersionUrl($parameter->getImage());
            }
            $tag = sprintf('<a href="%s" aria-label="Bild in größerer Version anzeigen">%s</a>', $link, $tag);
        }

        $generatedCaption = [];
        $figcaptionClass = [];

        if (!empty($parameter->getCaption())) {
            $generatedCaption[] = $parameter->getCaption();
            $figcaptionClass[] = 'cap';
        }

        if ($parameter->isHasExif()) {
            $e = (object) $parameter->getImage()->getExif();
            $eData = [];
            if (!empty($e->model)) {
                $eData[] = $this->matchExifTag($e->model);
            }
            if (!empty($e->lens) && '0.0 mm f/0.0' !== $e->lens) {
                $eData[] = $this->matchExifTag($this->matchLens($e->lens));
            }
            if (!empty($e->aperture)) {
                $eData[] = $e->aperture;
            }
            if (!empty($e->focallength)) {
                $eData[] = $e->focallength;
            }
            if (!empty($e->exposure)) {
                $eData[] = $this->matchExposure($e);
            }
            if (!empty($e->iso)) {
                $eData[] = $e->iso;
            }
            if (!empty($e->openStreetMap)) {
                $eData[] = sprintf(
                    '<a target="_blank" href="%s" aria-label="Ort auf OpenStreetMap anzeigen">OpenStreetMap</a>',
                    $e->openStreetMap
                );
            }
            if (!empty($e->googlemap) && empty($e->openStreetMap)) {
                $eData[] = sprintf(
                    '<a target="_blank" href="%s" aria-label="Ort auf Google Maps anzeigen">Google Maps</a>',
                    $e->googlemap
                );
            }
            $generatedCaption[] = '<p>'.implode(' &middot; ', $eData).'</p>';
            $figcaptionClass[] = 'exif';
        }

        if (!empty($generatedCaption)) {
            return sprintf(
                '<figure style="view-transition-name: figure-%d"%s>%s<figcaption class="%s">%s</figcaption></figure>',
                $parameter->getImage()->getId(),
                (!empty($parameter->getFigureClass()) ? ' class="'.$parameter->getFigureClass().'"' : ''),
                $tag,
                implode(' ', $figcaptionClass),
                implode(' ', $generatedCaption)
            );
        }

        return sprintf('<figure style="view-transition-name: figure-%d"%s>%s</figure>', $parameter->getImage()->getId(), (!empty($parameter->getFigureClass()) ? ' class="'.$parameter->getFigureClass().'"' : ''), $tag);
    }

    protected function getImageSize(Image $image, string $baseVersion): array
    {
        try {
            if (empty($image->getVersions())) {
                throw new Exception('Property versions does not exist. (1)');
            }

            $versions = $image->getVersions();
            $fileName = $versions[$baseVersion] ?? null;

            if (empty($fileName)) {
                throw new Exception('Could not determine filename for image.');
            }

            preg_match('/^.+-(\d{2,4})x(\d{2,4})\.jpeg$/', $fileName, $match);
            if (isset($match[1]) && isset($match[2])) {
                return ['width' => (int)$match[1], 'height' => (int)$match[2]];
            }
        } catch (Throwable) {
            return ['width' => null, 'height' => null];
        }

        return ['width' => null, 'height' => null];
    }

    protected function matchExifTag(?string $exifString = ''): string
    {
        return match (strtolower($exifString)) {
            // Camra
            'nikon z 7' => '<a href="https://kniebes.com/tag/nikon-z7.html">Nikon Z 7</a>',
            'nikon corporation nikon d800', 'nikon d800' => '<a href="https://kniebes.com/tag/nikon-d800.html">Nikon D800</a>',
            // Lens Nikon
            'nikkor z 26mm f/2.8' => '<a href="https://kniebes.com/tag/nikon-z-26mm-f-28.html">NIKKOR Z 26mm f/2.8</a>',
            'nikkor z mc 105mm f/2.8 vr s' => '<a href="https://kniebes.com/tag/nikon-z-mc-105mm-f-28-vr-s.html">NIKKOR Z MC 105mm f/2.8 VR S</a>',
            'nikkor z 50mm f/1.8 s' => '<a href="https://kniebes.com/tag/nikon-z-50mm-f-18-s.html">NIKKOR Z 50mm f/1.8 S</a>',
            'nikon 50 mm 1:1,4g', '50.0 mm f/1.4' => '<a href="https://kniebes.com/tag/nikon-50-mm-114g.html">Nikon 50 mm 1:1,4G</a>',
            'nikkor z mc 50mm f/2.8' => '<a href="https://kniebes.com/tag/nikon-z-50mm-f-28-s.html">NIKKOR Z MC 50mm f/2.8</a>',
            // Lens Voigtländer
            'voigtländer nokton 58 mm f/1.4 sl ii' => '<a href="https://kniebes.com/tag/voigtlaender-nokton-58mm-114-sl-ii.html">Voigtländer Nokton 58 mm f/1.4 SL II</a>',
            // Lens Zeiss
            'zeiss milvus 1.4/50 zf.2' => '<a href="https://kniebes.com/tag/zeiss-milvus-14-50mm.html">Zeiss Milvus 1.4/50 ZF.2</a>',
            'zeiss makro-planar t* 2/100 zf.2' => '<a href="https://kniebes.com/tag/zeiss-makro-planar-t-2-100-zf2.html">Zeiss Makro-Planar T* 2/100 ZF.2</a>',

            default => $exifString
        };
    }

    protected function matchLens(string $lens): string
    {
        return match (trim($lens)) {
            '58.0 mm f/1.4' => 'Voigtländer Nokton 58 mm f/1.4 SL II',
            '50.0 mm f/1.4' => 'Nikon 50 mm 1:1,4G',
            default => $lens
        };
    }

    protected function matchExposure(object $e): string
    {
        if ($e->model === 'Google Pixel 7 Pro') {
            return '';
        }

        return $e->exposure;
    }

    /**
     * @throws Exception
     */
    protected function getVersionPath($image, ?string $version = null): string
    {
        $photoBasedir = $this->getPhotoDomainDir();
        if (empty($version)) {
            return $photoBasedir.$image->url;
        }

        if (!in_array(intval($version), $this->getResolution())) {
            throw new Exception('Invalid version '.$version);
        }

        $path = $image->versions->$version ?? null;
        if (empty($path)) {
            return $photoBasedir.$image->url;
        }

        return $photoBasedir.$path;
    }

    /**
     * @throws Exception
     */
    protected function getVersionUrl(Image $image, ?string $version = null): string
    {
        if (empty($version)) {
            return sprintf('https://%s%s', $image->getDomain(), $image->getUrl());
        }

        if (!in_array(intval($version), $this->getResolution())) {
            throw new Exception('Invalid version '.$version);
        }

        $path = $image->getVersions()[$version] ?? null;
        if (empty($path)) {
            return sprintf('https://%s%s', $image->getDomain(), $image->getUrl());
        }

        return sprintf('https://%s%s', $image->getDomain(), $path);
    }

    /**
     * @throws Exception
     */
    protected function getPhotoDomainDir(): string
    {
        $path = $_ENV['LOCAL_PHOTO_DOMAIN_PATH'] ?? null;
        if (empty($path)) {
            throw new Exception('env(LOCAL_PHOTO_DOMAIN_PATH) is not configured!');
        }

        return $path;
    }

    protected function getResolution(): array
    {
        return [100, 800, 1024, 2048];
    }
}
