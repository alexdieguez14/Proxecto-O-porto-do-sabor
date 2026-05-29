<?php

namespace App\Controller\Admin;

use App\Entity\Categoria;
use App\Form\CategoriaFilterType;
use App\Form\CategoriaType;
use App\Repository\CategoriaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categorias')]
class CategoriaController extends AbstractController
{
    /** Listado de categorías con filtros */
    #[Route('', name: 'admin_categoria_index')]
    public function index(Request $request, CategoriaRepository $repo): Response
    {
        $form = $this->createForm(CategoriaFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('admin/categoria/index.html.twig', [
            'categorias' => $repo->buscarFiltrados($d['busqueda'] ?? null),
            'filtroForm' => $form->createView(),
        ]);
    }

    /** Crear nueva categoría */
    #[Route('/nueva', name: 'admin_categoria_nueva')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $categoria = new Categoria();
        $form = $this->createForm(CategoriaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($categoria);
            $em->flush();
            $this->addFlash('success', 'aviso.categoria.creada');
            return $this->redirectToRoute('admin_categoria_index');
        }

        return $this->render('admin/categoria/form.html.twig', [
            'form'   => $form,
            'titulo' => 'Nueva categoría',
        ]);
    }

    /** Editar categoría existente */
    #[Route('/{id}/editar', name: 'admin_categoria_editar')]
    public function edit(Categoria $categoria, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoriaType::class, $categoria);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'aviso.categoria.actualizada');
            return $this->redirectToRoute('admin_categoria_index');
        }

        return $this->render('admin/categoria/form.html.twig', [
            'form'   => $form,
            'titulo' => 'Editar categoría',
        ]);
    }
    
    /** Eliminar categoría */
    #[Route('/{id}/eliminar', name: 'admin_categoria_eliminar', methods: ['POST'])]
    public function delete(Categoria $categoria, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_categoria_' . $categoria->getId(), $request->request->get('_token'))) {
            $em->remove($categoria);
            $em->flush();
            $this->addFlash('success', 'aviso.categoria.eliminada');
        }
        return $this->redirectToRoute('admin_categoria_index');
    }
}
