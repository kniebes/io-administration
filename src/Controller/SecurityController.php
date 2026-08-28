<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
            view: 'security/login.html.twig',
            parameters: [
                'lastUsername' => $authenticationUtils->getLastUsername(),
                'authenticationError' => $authenticationUtils->getLastAuthenticationError(),
            ],
        );
    }
}
