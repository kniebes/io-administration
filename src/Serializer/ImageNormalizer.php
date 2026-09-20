<?php declare(strict_types=1);

namespace App\Serializer;

use App\Entity\Image;
use App\Model\ContentApi\RequestConfigData;
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
        $config = $this->getConfigFromContext($context);
        $normalized = $this->objectNormalizer->normalize(data: $data, format: $format, context: $context);

        if (in_array('blog_post:read', $context['groups'] ?? [], true)) {
            $normalized['figureTag'] = $this->buildFigureTag(image: $data, config: $config);
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

    private function buildFigureTag(Image $image, ?RequestConfigData $config = null): ?string
    {
        $imageContentWith = $config?->getImageContentWidth() ?? null;

        return $this->figureTagFactory->create(image: $image, imageContentWidth: $imageContentWith);
    }

    private function getConfigFromContext(array $context): ?RequestConfigData
    {
        return isset($context['config']) && ($context['config'] instanceof RequestConfigData)
            ? $context['config']
            : null;
    }
}
