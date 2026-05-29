<?php

namespace App\Controller\Admin;

use App\Entity\Proveedor;
use App\Form\ProveedorFilterType;
use App\Form\ProveedorType;
use App\Repository\ProveedorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProveedorController extends AbstractController
{

    /** Listado de proveedores con filtros */
    #[Route('/admin/proveedores',       name: 'admin_proveedor_index')]
    #[Route('/contabilidad/proveedores', name: 'contabilidad_proveedor_index')]
    public function index(Request $request, ProveedorRepository $repo): Response
    {
        $form = $this->createForm(ProveedorFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('admin/proveedor/index.html.twig', [
            'proveedores' => $repo->buscarFiltrados($d['busqueda'] ?? null),
            'filtroForm'  => $form->createView(),
        ]);
    }

    /** Crear nuevo proveedor */
    #[Route('/admin/proveedores/nuevo',       name: 'admin_proveedor_nuevo')]
    #[Route('/contabilidad/proveedores/nuevo', name: 'contabilidad_proveedor_nuevo')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $proveedor = new Proveedor();
        $form = $this->createForm(ProveedorType::class, $proveedor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($proveedor);
            $em->flush();
            $this->addFlash('success', 'aviso.proveedor.creado');

            return $this->redirectToRoute($this->indexRoute($request));
        }

        return $this->render('admin/proveedor/form.html.twig', [
            'form'   => $form,
            'titulo' => 'Nuevo proveedor',
        ]);
    }

    /** Editar proveedor existente */
    #[Route('/admin/proveedores/{id}/editar',       name: 'admin_proveedor_editar')]
    #[Route('/contabilidad/proveedores/{id}/editar', name: 'contabilidad_proveedor_editar')]
    public function edit(Proveedor $proveedor, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProveedorType::class, $proveedor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'aviso.proveedor.actualizado');

            return $this->redirectToRoute($this->indexRoute($request));
        }

        return $this->render('admin/proveedor/form.html.twig', [
            'form'   => $form,
            'titulo' => 'Editar proveedor',
        ]);
    }

    #[Route('/admin/proveedores/{id}/eliminar',       name: 'admin_proveedor_eliminar',       methods: ['POST'])]
    #[Route('/contabilidad/proveedores/{id}/eliminar', name: 'contabilidad_proveedor_eliminar', methods: ['POST'])]
    public function delete(Proveedor $proveedor, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_proveedor_' . $proveedor->getId(), $request->request->get('_token'))) {
            $em->remove($proveedor);
            $em->flush();
            $this->addFlash('success', 'aviso.proveedor.eliminado');
        }

        return $this->redirectToRoute($this->indexRoute($request));
    }

    /// Devuelve la ruta de listado correspondiente según el contexto (admin o contabilidad)
    private function indexRoute(Request $request): string
    {
        return str_contains((string) $request->attributes->get('_route'), 'contabilidad')
            ? 'contabilidad_proveedor_index'
            : 'admin_proveedor_index';
    }
}
