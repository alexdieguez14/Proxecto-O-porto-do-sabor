<?php

namespace App\Controller;

use App\Form\FormularioAccesoType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SeguridadController extends AbstractController
{
    /* Página de acceso/login */
    #[Route('/acceso', name: 'app_acceso')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_inicio');
        }

        $form = $this->createForm(FormularioAccesoType::class, [
            'email' => $authenticationUtils->getLastUsername(),
        ]);

        return $this->render('security/login.html.twig', [
            'form'  => $form,
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /* La ruta de logout es manejada por Symfony */
    #[Route('/cerrar-sesion', name: 'app_cerrar_sesion')]
    public function logout(): void {}
}
