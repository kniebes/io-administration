<?php

declare(strict_types=1);

namespace App\Controller;

use Kniebes\IoCore\Entity\BlogPost;
use Kniebes\IoCore\Repository\BlogPostRepository;
use Knp\Component\Pager\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogPostController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly BlogPostRepository $blogPostRepository,
    )
    {
    }

    #[Route('/blog-posts')]
    public function index(Request $request): Response
    {
        $pagination = $this->paginator->paginate(
            target: $this->blogPostRepository->createSearchQuery(),
            page: $request->query->getInt('page', 1),
            limit: 10
        );

        return $this->render('blog_post/index.html.twig', ['pagination' => $pagination]);
    }
    #[Route('/edit-blog-post/{id}', name: 'blog_post_edit', methods: ['GET'])]
    public function edit(Request $request, BlogPost $blogPost): Response
    {
        return $this->render('blog_post/edit.html.twig', ['blogPost' => $blogPost]);
    }
}
