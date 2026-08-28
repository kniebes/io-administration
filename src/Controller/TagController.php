<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class TagController extends AbstractController
{
    private const int SEARCH_RESULT_LIMIT = 10;

    public function __construct(
        private readonly TagRepository $tagRepository,
    )
    {
    }

    #[Route('/tags/search', name: 'tag_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query->get('query', ''));

        if ($term === '') {
            return new JsonResponse([]);
        }

        $tags = $this->tagRepository->findByTerm(term: $term, limit: self::SEARCH_RESULT_LIMIT);

        return new JsonResponse(array_map(
            static fn (Tag $tag): array => ['id' => $tag->getId(), 'term' => $tag->getTerm()],
            $tags,
        ));
    }
}
