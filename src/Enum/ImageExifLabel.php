<?php declare(strict_types=1);

namespace App\Enum;

enum ImageExifLabel: string
{
    case Model = 'model';
    case Lens = 'lens';
    case ISO = 'iso';
    case Aperture = 'aperture';
    case FocalLength = 'focal_length';
    case ExposureTime = 'exposure_time';
    case UnknownLabel = 'unknown';
}
