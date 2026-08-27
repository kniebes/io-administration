<?php

declare(strict_types=1);

namespace App\Controller;

use App\Event\SavedBlogPostEvent;
use App\Form\BlogPostFormType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Kniebes\IoCore\Entity\BlogPost;
use Kniebes\IoCore\Repository\BlogPostRepository;
use Knp\Component\Pager\PaginatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Turbo\TurboBundle;

#[IsGranted('ROLE_USER')]
final class BlogPostController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly BlogPostRepository $blogPostRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    #[Route('/blogposts', name: 'blog_post_index')]
    public function index(Request $request): Response
    {
        $pagination = $this->paginator->paginate(
            target: $this->blogPostRepository->createSearchQuery(),
            page: $request->query->getInt('page', 1),
            limit: 10
        );

        return $this->render(view: 'blog_post/index.html.twig', parameters: ['pagination' => $pagination]);
    }

    #[Route('/blogpost/add', name: 'blog_post_add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $blogPost = (new BlogPost())
            ->setTitle('')
            ->setSlug('')
            ->setContent('');

        $form = $this->createForm(type: BlogPostFormType::class, data: $blogPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($blogPost);
            $this->entityManager->flush();
            $this->eventDispatcher->dispatch(New SavedBlogPostEvent($blogPost));

            return $this->redirectToRoute(route: 'blog_post_edit', parameters: ['id' => $blogPost->getId()]);
        }

        if ($form->isSubmitted() && $this->isTurboStreamRequest($request)) {
            return $this->renderSaveInfoStream(request: $request, success: false, form: $form, blogPost: $blogPost);
        }

        return $this->render(view: 'blog_post/edit.html.twig', parameters: ['form' => $form, 'blogPost' => $blogPost]);
    }

    #[Route('/blogpost/edit/{id}', name: 'blog_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BlogPost $blogPost): Response
    {
        $form = $this->createForm(type: BlogPostFormType::class, data: $blogPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            if ($this->isTurboStreamRequest($request)) {
                $this->eventDispatcher->dispatch(New SavedBlogPostEvent($blogPost));
                return $this->renderSaveInfoStream(request: $request, success: true, form: $form, blogPost: $blogPost);
            }

            $this->eventDispatcher->dispatch(New SavedBlogPostEvent($blogPost));

            return $this->redirectToRoute(route: 'blog_post_edit', parameters: ['id' => $blogPost->getId()]);
        }

        if ($form->isSubmitted() && $this->isTurboStreamRequest($request)) {
            return $this->renderSaveInfoStream(request: $request, success: false, form: $form, blogPost: $blogPost);
        }

        return $this->render(view: 'blog_post/edit.html.twig', parameters: ['form' => $form, 'blogPost' => $blogPost]);
    }

    #[Route('/blogpost/delete/{id}', name: 'blog_post_delete', methods: ['POST'])]
    public function delete(Request $request, BlogPost $blogPost): Response
    {
        $isValidToken = $this->isCsrfTokenValid(
            id: 'delete-blog-post-' . $blogPost->getId(),
            token: $request->request->get('_csrf_token'),
        );

        if ($isValidToken) {
            $this->entityManager->remove($blogPost);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute(route: 'blog_post_index');
    }

    private function isTurboStreamRequest(Request $request): bool
    {
        return $request->getPreferredFormat() === TurboBundle::STREAM_FORMAT;
    }

    private function renderSaveInfoStream(
        Request $request,
        bool $success,
        FormInterface $form,
        BlogPost $blogPost,
    ): Response
    {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->render(view: 'blog_post/_save_info.stream.html.twig', parameters: [
            'form' => $form,
            'blogPost' => $blogPost,
            'success' => $success,
            'savedAt' => $blogPost->getUpdated(),
            'errors' => $errors,
        ]);
    }
}
