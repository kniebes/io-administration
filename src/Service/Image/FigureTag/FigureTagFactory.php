<?php declare(strict_types=1);

namespace App\Service\Image\FigureTag;

use App\Entity\Image;
use Throwable;
use Twig\Environment;

class FigureTagFactory
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function create(Image $image, ?string $contentWidth = null): string
    {
        $contentWidth = $contentWidth ?? '1024px';
        try {
            return $this->twig->render('figure_tag/figure.html.twig', [
                'image' => $image,
                'content_width' => $contentWidth,
                'hasFigure' => true,
                'figCaption' => $image->getCustomFields()['caption'] ?? null,
                'figureClasses' => '',
                'figureStyles' => $this->calcFigureStyles($image),
                'hasAnker' => true,
                'ankerUrl' => $image->getHost().$image->getUrl(),
                'ankerAttributes' => '',
            ]);
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    private function calcFigureStyles(Image $image): string
    {
        return sprintf(
            'aspect-ratio: %.2f; view-transition-name: figure-%d',
            $image->getAspectRatio(),
            $image->getId()
        );
    }
}
