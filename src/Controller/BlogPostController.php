<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BlogPostController extends AbstractController
{
    #[Route('/blog-post')]
    public function index(): Response
    {
        return $this->render('blog_post/index.html.twig');
    }
}
