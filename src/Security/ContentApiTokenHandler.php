<?php declare(strict_types=1);

namespace App\Security;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ContentApiTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        #[Autowire(env: 'CONTENT_API_TOKEN')]
        private readonly string $apiToken,
    )
    {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        if ($this->apiToken === '') {
            throw new BadCredentialsException('No API token configured.');
        }

        if (!hash_equals($this->apiToken, $accessToken)) {
            throw new BadCredentialsException('Invalid API token.');
        }

        return new UserBadge('api', static fn(): ContentApiUser => new ContentApiUser());
    }
}
