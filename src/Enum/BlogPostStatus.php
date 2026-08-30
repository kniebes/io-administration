<?php declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum BlogPostStatus: string implements TranslatableInterface
{
    case Published = 'published';

    case Draft = 'draft';

    case Hidden = 'hidden';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        // Translate enum from name (Left, Center or Right)
//        return $translator->trans($this->name, locale: $locale);

        // Translate enum using custom labels
        return match ($this) {
            self::Published  => $translator->trans('blogpost.status.published', locale: $locale),
            self::Draft => $translator->trans('blogpost.status.draft', locale: $locale),
            self::Hidden  => $translator->trans('blogpost.status.hidden', locale: $locale),
        };
    }
}

