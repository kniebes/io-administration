<?php declare(strict_types=1);

namespace App\Serializer;

use App\Entity\BlogPost;
use App\Enum\LinkType;
use App\Service\BlogPost\PermaLinkFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class BlogPostNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $objectNormalizer,
        private PermaLinkFactory $permalinkFactory,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $normalized = $this->objectNormalizer->normalize(data: $data, format: $format, context: $context);

        if (in_array('blog_post:read', $context['groups'] ?? [], true)) {
            $normalized['permaLink'] = $this->buildPermaLink($data);
            $normalized['contentEncoded'] = $this->replaceLinks($data);
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BlogPost;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [BlogPost::class => true];
    }

    private function replaceLinks(BlogPost $blogPost): string
    {
        $contentEncoded = $blogPost->getContentEncoded();
        foreach ($blogPost->getLinks() as $link) {
            if ($link->getType() !== LinkType::BlogPostLink || empty($link->getAlternateUrl())) {
                continue;
            }
            $contentEncoded = str_replace($link->getUrl(), $link->getAlternateUrl(), $contentEncoded);
        }

        return $contentEncoded;
    }

    private function buildPermaLink(BlogPost $blogPost): ?string
    {
        if (empty($blogPost->getBlog()) || empty($blogPost->getPublishedDate())) {
            return null;
        }

        return $this->permalinkFactory->createFormBlogPost(blogPost: $blogPost);
    }
}
