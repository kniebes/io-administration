<?php declare(strict_types=1);

namespace App\Enum;

enum BlogPostStatus: string
{
    case Published = 'published';

    case Draft = 'draft';

    case Hidden = 'hidden';
}

