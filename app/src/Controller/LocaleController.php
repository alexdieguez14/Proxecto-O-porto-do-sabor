<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    private const SUPPORTED = ['es', 'en', 'gl'];


    /** Cambia el idioma de la sesión */
    #[Route('/locale/{code}', name: 'app_locale_switch', methods: ['GET', 'POST'])]
    public function switch(string $code, Request $request): Response
    {
        if (in_array($code, self::SUPPORTED, true)) {
            $request->getSession()->set('_locale', $code);
        }

        $referer = $request->headers->get('referer');
        if ($referer) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToRoute('app_acceso');
    }
}
