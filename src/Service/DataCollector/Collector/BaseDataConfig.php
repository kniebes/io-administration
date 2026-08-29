<?php declare(strict_types=1);

namespace App\Service\DataCollector\Collector;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BaseDataConfig
{
    public function __construct(
        #[Autowire(env: 'SITE_NAME')] public string $name,
        #[Autowire(env: 'SITE_SUB_TITLE')] public string $subtitle,
        #[Autowire(env: 'SITE_DOMAIN')] public string $domain,
        #[Autowire(env: 'SITE_CONTACT_EMAIL')] public string $contactEmail,
        #[Autowire(env: 'json:SITE_SOCIAL_LINKS')] public array $socialLinks,
    )
    {
    }
}
