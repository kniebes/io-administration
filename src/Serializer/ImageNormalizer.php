<?php declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Image;
use App\Service\Image\FigureTag\FigureTagFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ImageNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $objectNormalizer,
        private FigureTagFactory $figureTagFactory,
    ) {
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        $normalized = $this->objectNormalizer->normalize(data: $data, format: $format, context: $context);

        if (in_array('blog_post:read', $context['groups'] ?? [], true)) {
            $normalized['figureTag'] = $this->buildFigureTag(image: $data, context: $context);
        }

        return $normalized;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Image;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Image::class => true];
    }

    private function buildFigureTag(Image $image, array $context = []): ?string
    {
        return $this->figureTagFactory->create(image: $image, contentWidth: $context['content_width'] ?? null);
    }
}
