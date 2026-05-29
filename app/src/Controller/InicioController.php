<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InicioController extends AbstractController
{

    /** Página de inicio que redirige según el rol del usuario */
    #[Route('/', name: 'app_inicio')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }
        if ($this->isGranted('ROLE_LOGISTICA')) {
            return $this->redirectToRoute('app_logistica');
        }
        if ($this->isGranted('ROLE_CLIENTE')) {
            return $this->redirectToRoute('app_tienda');
        }
        if ($this->isGranted('ROLE_CONTABILIDAD')) {
            return $this->redirectToRoute('app_contabilidad');
        }

        return $this->redirectToRoute('app_acceso');
    }
}
