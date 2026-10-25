<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Image;
use App\Event\ImagePreSaveEvent;
use App\Form\Filter\ImageIndexFilterType;
use App\Form\ImageFormType;
use App\Model\Filter\ImageIndexFilter;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Turbo\TurboBundle;

#[IsGranted('ROLE_USER')]
final class ImageController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly ImageRepository $imageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route(
        path: '/images',
        name: 'image_index',
        options: [
            'navigation' => ['title' => 'navigation.image_index', 'position' => 20],
        ]
    )]
    public function index(Request $request): Response
    {
        $filter = new ImageIndexFilter();
        $filterForm = $this->createForm(type: ImageIndexFilterType::class, data: $filter);
        $filterForm->handleRequest($request);

        if ($filterForm->isSubmitted() && $filterForm->get('reset')->isClicked()) {
            return $this->redirectToRoute(route: 'image_index');
        }

        $pagination = $this->paginator->paginate(
            target: $this->imageRepository->createFilterQuery($filter),
            page: $request->query->getInt('page', 1),
            limit: 12
        );

        return $this->render(
            view: 'image/index.html.twig',
            parameters: [
                'pagination' => $pagination,
                'filterForm' => $filterForm,
            ]
        );
    }

    #[Route(
        path: '/image/edit/{id}',
        name: 'image_edit',
        methods: ['GET', 'POST']
    )]
    public function edit(Request $request, Image $image): Response
    {
        $form = $this->createForm(type: ImageFormType::class, data: $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventDispatcher->dispatch(new ImagePreSaveEvent($image));
            $this->entityManager->flush();

            if ($this->isTurboStreamRequest($request)) {
                return $this->renderEditStream(request: $request, success: true, form: $form, image: $image);
            }

            return $this->redirectToRoute(route: 'image_edit', parameters: ['id' => $image->getId()]);
        }

        if ($form->isSubmitted() && $this->isTurboStreamRequest($request)) {
            return $this->renderEditStream(request: $request, success: false, form: $form, image: $image);
        }

        return $this->render(view: 'image/edit.html.twig', parameters: [
            'form' => $form,
            'image' => $image,
        ]);
    }

    private function isTurboStreamRequest(Request $request): bool
    {
        return $request->getPreferredFormat() === TurboBundle::STREAM_FORMAT;
    }

    private function renderEditStream(
        Request $request,
        bool $success,
        FormInterface $form,
        Image $image,
    ): Response {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return $this->render(view: 'image/_edit.stream.html.twig', parameters: [
            'form' => $form,
            'image' => $image,
            'success' => $success,
            'savedAt' => $image->getUpdated(),
            'errors' => $errors,
        ]);
    }
}
