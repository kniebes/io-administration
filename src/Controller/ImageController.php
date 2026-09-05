<?php declare(strict_types=1);

namespace App\Controller;

use App\Form\Filter\ImageIndexFilterType;
use App\Model\Filter\ImageIndexFilter;
use App\Repository\ImageRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ImageController extends AbstractController
{
    public function __construct(
        private readonly PaginatorInterface $paginator,
        private readonly ImageRepository $imageRepository,

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
            target: $this->imageRepository->createFilterQuery(),
            page: $request->query->getInt('page', 1),
            limit: 20
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
    public function image(Request $request): Response
    {

    }
}
