<?php declare(strict_types=1);

namespace App\Service\IndieWeb\Syndication\Syndicator\Interface;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.indieweb_syndictor')]
interface SyndicatorInterface
{
    public function syndicate(int $blogPostId, int $blogId): void;
}
