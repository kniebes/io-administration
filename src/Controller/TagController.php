<?php

declare(strict_types=1);

namespace App\Controller;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Kniebes\IoCore\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
final class TagController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    )
    {
    }

    #[Route('/tags/create', name: 'tag_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $payload = $request->toArray();

        $isValidToken = $this->isCsrfTokenValid(id: 'tag-create', token: (string) ($payload['_csrf_token'] ?? ''));

        if (!$isValidToken) {
            return new JsonResponse(data: ['error' => 'Invalid CSRF token'], status: 403);
        }

        $term = trim((string) ($payload['term'] ?? ''));

        if ($term === '') {
            return new JsonResponse(data: ['error' => 'Term must not be empty'], status: 422);
        }

        $slug = strtolower($this->slugger->slug($term)->toString());
        $tag = $this->entityManager->getRepository(Tag::class)->findOneBy(['slug' => $slug]);

        if (!$tag) {
            $now = new DateTimeImmutable();
            $tag = (new Tag())
                ->setTerm($term)
                ->setSlug($slug)
                ->setCreated($now)
                ->setUpdated($now);

            $this->entityManager->persist($tag);
            $this->entityManager->flush();
        }

        return new JsonResponse(data: ['id' => $tag->getId(), 'term' => $tag->getTerm()], status: 201);
    }
}
