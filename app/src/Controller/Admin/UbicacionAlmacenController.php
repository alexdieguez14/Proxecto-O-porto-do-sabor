<?php

namespace App\Controller\Admin;

use App\Entity\UbicacionAlmacen;
use App\Form\UbicacionAlmacenFilterType;
use App\Form\UbicacionAlmacenType;
use App\Repository\UbicacionAlmacenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ubicaciones')]
class UbicacionAlmacenController extends AbstractController
{
    /** Listado de ubicaciones con filtros */
    #[Route('', name: 'admin_ubicacion_index')]
    public function index(Request $request, UbicacionAlmacenRepository $repo): Response
    {
        $form = $this->createForm(UbicacionAlmacenFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('admin/ubicacion/index.html.twig', [
            'ubicaciones' => $repo->buscarFiltrados($d['busqueda'] ?? null),
            'filtroForm'  => $form->createView(),
        ]);
    }

    /** Crear nueva ubicación */
    #[Route('/nueva', name: 'admin_ubicacion_nueva')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $ubicacion = new UbicacionAlmacen();
        $form = $this->createForm(UbicacionAlmacenType::class, $ubicacion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ubicacion);
            $em->flush();
            $this->addFlash('success', 'aviso.ubicacion.creada');
            return $this->redirectToRoute('admin_ubicacion_index');
        }

        return $this->render('admin/ubicacion/form.html.twig', [
            'form'   => $form,
            'titulo' => 'Nueva ubicación',
        ]);
    }

    /** Editar ubicación existente */
     #[Route('/{id}/editar', name: 'admin_ubicacion_editar')]
     public function edit(UbicacionAlmacen $ubicacion, Request $request, EntityManagerInterface $em): Response
     {
         $form = $this->createForm(UbicacionAlmacenType::class, $ubicacion);
         $form->handleRequest($request);

         if ($form->isSubmitted() && $form->isValid()) {
             $em->flush();
             $this->addFlash('success', 'aviso.ubicacion.actualizada');
             return $this->redirectToRoute('admin_ubicacion_index');
         }

         return $this->render('admin/ubicacion/form.html.twig', [
             'form'   => $form,
             'titulo' => 'Editar ubicación',
         ]);
     }

    /** Eliminar ubicación */
    #[Route('/{id}/eliminar', name: 'admin_ubicacion_eliminar', methods: ['POST'])]
    public function delete(UbicacionAlmacen $ubicacion, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_ubicacion_' . $ubicacion->getId(), $request->request->get('_token'))) {
            $em->remove($ubicacion);
            $em->flush();
            $this->addFlash('success', 'aviso.ubicacion.eliminada');
        }
        return $this->redirectToRoute('admin_ubicacion_index');
    }
}
