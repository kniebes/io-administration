<?php declare(strict_types=1);

namespace App\Service\Image\Upload;

class ExifParser
{
    public function generateExif(string $filename): array
    {
        $fileData = exif_read_data($filename, 'IFD0,EXIF', true);
        if (empty($fileData['IFD0']) || empty($fileData['EXIF'])) {
            return [];
        }

        $iptcTitle = '';
        getimagesize($filename, $info);
        if (isset($info['APP13'])) {
            $iptc = iptcparse($info['APP13']);
            $iptcTitle = $iptc['2#005'][0] ?? '';
        }

        $model = ucwords(strtolower($fileData['IFD0']['Make']??'')).' '.($fileData['IFD0']['Model']??'');
        $model = match ($model) {
            'Nikon Corporation NIKON Z 8' => 'Nikon Z 8',
            'Nikon Corporation NIKON Z 7' => 'Nikon Z 7',
            default => $model
        };

        $imageDescription = $fileData['IFD0']['ImageDescription'] ?? '';
        $copyright = ucfirst(strtolower($fileData['IFD0']['Copyright'] ?? ''));
        $exposure = '';
        if (isset($fileData['EXIF']['ExposureTime'])) {
            $exposure = $fileData['EXIF']['ExposureTime'].'s';
        }
        if ($model === 'Google Pixel 7 Pro') {
            $exposure = $fileData['EXIF']['ShutterSpeedValue'].'s';
        }

        $lon = $lat = $alt = $googlemap = $openStreetMap = '';
        if (!empty($fileData['GPS'])) {
            if (!empty($fileData['GPS']["GPSLongitude"]) && !empty($fileData['GPS']['GPSLongitudeRef'])) {
                $lon = $this->getGps($fileData['GPS']["GPSLongitude"], $fileData['GPS']['GPSLongitudeRef']);
            }

            if (!empty($fileData['GPS']["GPSLatitude"]) && !empty($fileData['GPS']['GPSLatitudeRef'])) {
                $lat = $this->getGps($fileData['GPS']["GPSLatitude"], $fileData['GPS']['GPSLatitudeRef']);
            }

            if (!empty($fileData['GPS']['GPSAltitude'])) {
                $alt = number_format($this->gps2Num($fileData['GPS']['GPSAltitude']), 8, '.', ',');
            }

            if (!empty($lat) && !empty($lon)) {
                // http://www.google.com/maps/place/49.46800006494457,17.11514008755796
                $googlemap = sprintf('https://www.google.de/maps/place/%s,%s', $lat, $lon);
                // https://www.openstreetmap.org/#map=19/51.47213/7.21484
                $openStreetMap = sprintf('https://www.openstreetmap.org/#map=19/%s/%s', $lat, $lon);
            }
        }

        return [
            'title' => $iptcTitle,
            'description' => $imageDescription,
            'copyright' => $copyright,
            'model' => $model,
            'lens' => $fileData['EXIF']['UndefinedTag:0xA434'] ?? '',
            'focallength' => $this->parseFocalLength($fileData['EXIF']['FocalLength']??''),
            'aperture' => $fileData['COMPUTED']['ApertureFNumber']??'',
            'exposure' => $exposure,
            'iso' => 'ISO '.($fileData['EXIF']['ISOSpeedRatings']??''),
            'time' => date('d.m.Y H:i', (int) strtotime($fileData['EXIF']['DateTimeOriginal']??'')),
            'longitude' => $lon,
            'latitude' => $lat,
            'altitude' => $alt,
            'googlemap' => $googlemap,
            'openStreetMap' => $openStreetMap,
        ];
    }

    protected function parseFocalLength($value): string|float
    {
        $pos = strpos($value, '/');
        if ($pos === false) {
            return $value;
        }

        $a = (float) substr($value, 0, $pos);
        $b = (float) substr($value, $pos+1);

        return ($b == 0) ? ($a) : ($a / $b).'mm';
    }

    protected function getGps($exifCoord, $hemi): string
    {
        $degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
        $minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
        $seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

        $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

        return number_format($flip * ($degrees + $minutes / 60 + $seconds / 3600), 8, '.', ',');
    }



    protected function gps2Num($coordPart): float
    {
        $parts = explode('/', $coordPart);

        if (count($parts) <= 0)
            return 0;

        if (count($parts) == 1)
            return (float) $parts[0];

        return floatval($parts[0]) / floatval($parts[1]);
    }
}
