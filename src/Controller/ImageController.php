<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\Image;
use App\Event\ImagePreSaveEvent;
use App\Form\Filter\ImageIndexFilterType;
use App\Form\ImageFormType;
use App\Model\Filter\ImageIndexFilter;
use App\Repository\ImageRepository;
use App\Service\Image\ImageRemover;
use App\Service\Image\Upload\Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Turbo\TurboBundle;

#[IsGranted('ROLE_USER')]
final class ImageController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly ImageRepository $imageRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Uploader $uploader,
        private readonly TranslatorInterface $translator,
        private readonly ImageRemover $imageRemover,
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

        $imageIds = array_map(
            static fn (Image $image): int => $image->getId(),
            iterator_to_array($pagination->getItems(), preserve_keys: false),
        );

        return $this->render(
            view: 'image/index.html.twig',
            parameters: [
                'pagination' => $pagination,
                'filterForm' => $filterForm,
                'blogPostCounts' => $this->imageRepository->countBlogPostsByImageIds($imageIds),
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

    #[Route(
        path: '/image/upload',
        name: 'image_upload',
        methods: ['POST']
    )]
    public function upload(Request $request): JsonResponse
    {
        $isValidToken = $this->isCsrfTokenValid(
            id: 'image-upload',
            token: $request->request->get('_csrf_token'),
        );

        if (!$isValidToken) {
            return $this->createUploadErrorResponse(messageKey: 'image.index.upload.error.csrf', status: Response::HTTP_FORBIDDEN);
        }

        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
            return $this->createUploadErrorResponse(messageKey: 'image.index.upload.error.invalid_file', status: Response::HTTP_BAD_REQUEST);
        }

        try {
            $image = $this->uploader->upload($uploadedFile);
        } catch (RuntimeException) {
            return $this->createUploadErrorResponse(messageKey: 'image.index.upload.error.unsupported_type', status: Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        if ($image === null) {
            return $this->createUploadErrorResponse(messageKey: 'image.index.upload.error.failed', status: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(
            data: [
                'id' => $image->getId(),
                'title' => $image->getTitle(),
                'editUrl' => $this->generateUrl(route: 'image_edit', parameters: ['id' => $image->getId()]),
            ],
            status: Response::HTTP_CREATED,
        );
    }

    #[Route(
        path: '/image/delete/{id}',
        name: 'image_delete',
        methods: ['POST']
    )]
    public function delete(Request $request, Image $image): Response
    {
        $isValidToken = $this->isCsrfTokenValid(
            id: 'delete-image-' . $image->getId(),
            token: $request->request->get('_csrf_token'),
        );

        if ($isValidToken) {
            $this->imageRemover->remove($image);
        }

        return $this->redirectToRoute(route: 'image_index');
    }

    private function createUploadErrorResponse(string $messageKey, int $status): JsonResponse
    {
        return new JsonResponse(
            data: ['message' => $this->translator->trans($messageKey)],
            status: $status,
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
