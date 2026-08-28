<?php declare(strict_types=1);

namespace App\Service\IoTag\Encoder\Interface;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.io_tag_encoder')]
interface IoTagEncoderInterface
{
    public function encode(string $string): string;
}
