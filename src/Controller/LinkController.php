<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Link;
use App\Enum\LinkType;
use App\Event\LinkPreSaveEvent;
use App\Form\Filter\LinkIndexFilterType;
use App\Form\LinkAddFormType;
use App\Form\LinkFormType;
use App\Model\Filter\LinkIndexFilter;
use App\Repository\LinkRepository;
use App\Service\Link\SiteDataFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\PaginatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

#[IsGranted('ROLE_USER')]
final class LinkController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly LinkRepository $linkRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SiteDataFetcher $siteDataFetcher,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route(
        path: '/links',
        name: 'link_index',
        options: [
            'navigation' => ['title' => 'navigation.link_index', 'position' => 40],
        ]
    )]
    public function index(Request $request): Response
    {
        $filter = new LinkIndexFilter();
        $filterForm = $this->createForm(type: LinkIndexFilterType::class, data: $filter);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->get('reset')->isClicked()) {
            return $this->redirectToRoute(route: 'link_index');
        }

        return $this->renderIndex(
            request: $request,
            filter: $filter,
            filterForm: $filterForm,
            addForm: $this->createAddForm(new Link()),
        );
    }

    #[Route(
        path: '/link/add',
        name: 'link_add',
        methods: ['POST']
    )]
    public function add(Request $request): Response
    {
        $link = new Link();

        $form = $this->createAddForm($link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $siteData = $this->siteDataFetcher->fetch($link->getUrl());

                $link
                    ->setTitle($siteData['title'] ?? null)
                    ->setQuote($siteData['description'] ?? null);

                $this->eventDispatcher->dispatch(new LinkPreSaveEvent($link));
                $this->entityManager->persist($link);
                $this->entityManager->flush();

                return $this->redirectToRoute(route: 'link_edit', parameters: ['id' => $link->getId()]);
            } catch (RuntimeException) {
                $form->get('url')->addError(new FormError($this->translator->trans('link.add.error.fetch')));
            }
        }

        $filter = new LinkIndexFilter();

        return $this->renderIndex(
            request: $request,
            filter: $filter,
            filterForm: $this->createForm(type: LinkIndexFilterType::class, data: $filter),
            addForm: $form,
        );
    }

    #[Route(
        path: '/link/edit/{id}',
        name: 'link_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, Link $link): Response
    {
        if ($link->getType() !== LinkType::Bookmark) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(type: LinkFormType::class, data: $link);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventDispatcher->dispatch(new LinkPreSaveEvent($link));
            $this->entityManager->flush();

            if ($this->isTurboStreamRequest($request)) {
                return $this->renderEditStream(request: $request, success: true, form: $form, link: $link);
            }

            return $this->redirectToRoute(route: 'link_edit', parameters: ['id' => $link->getId()]);
        }

        if ($form->isSubmitted() && $this->isTurboStreamRequest($request)) {
            return $this->renderEditStream(request: $request, success: false, form: $form, link: $link);
        }

        return $this->render(view: 'link/edit.html.twig', parameters: [
            'form' => $form,
            'link' => $link,
        ]);
    }

    #[Route(
        path: '/link/delete/{id}',
        name: 'link_delete',
        methods: ['POST']
    )]
    public function delete(Request $request, Link $link): Response
    {
        if ($link->getType() !== LinkType::Bookmark) {
            throw $this->createNotFoundException();
        }

        $isValidToken = $this->isCsrfTokenValid(
            id: 'delete-link-' . $link->getId(),
            token: $request->request->get('_csrf_token'),
        );

        if ($isValidToken) {
            $this->entityManager->remove($link);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute(route: 'link_index');
    }

    private function createAddForm(Link $link): FormInterface
    {
        return $this->createForm(
            type: LinkAddFormType::class,
            data: $link,
            options: ['action' => $this->generateUrl(route: 'link_add')],
        );
    }

    private function renderIndex(
        Request $request,
        LinkIndexFilter $filter,
        FormInterface $filterForm,
        FormInterface $addForm,
    ): Response {
        $pagination = $this->paginator->paginate(
            target: $this->linkRepository->createFilterQuery($filter),
            page: $request->query->getInt('page', 1),
            limit: 25
        );

        if ($pagination instanceof SlidingPagination) {
            $pagination->setUsedRoute('link_index');
        }

        return $this->render(
            view: 'link/index.html.twig',
            parameters: [
                'pagination' => $pagination,
                'filterForm' => $filterForm,
                'addForm' => $addForm,
            ]
        );
    }

    private function isTurboStreamRequest(Request $request): bool
    {
        return $request->getPreferredFormat() === TurboBundle::STREAM_FORMAT;
    }

    private function renderEditStream(
        Request $request,
        bool $success,
        FormInterface $form,
        Link $link,
    ): Response {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->render(view: 'link/_edit.stream.html.twig', parameters: [
            'form' => $form,
            'link' => $link,
            'success' => $success,
            'savedAt' => $link->getUpdated(),
            'errors' => $errors,
        ]);
    }
}
