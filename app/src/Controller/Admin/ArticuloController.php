<?php

namespace App\Controller\Admin;

use App\Entity\Articulo;
use App\Form\ArticuloAdminFilterType;
use App\Form\ArticuloType;
use App\Repository\ArticuloRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/admin/articulos')]
class ArticuloController extends AbstractController
{
    /** Listado de artículos con filtros */
    #[Route('', name: 'admin_articulo_index')]
    public function index(Request $request, ArticuloRepository $repo): Response
    {
        $form = $this->createForm(ArticuloAdminFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('admin/articulo/index.html.twig', [
            'articulos'  => $repo->buscarFiltrados($d['busqueda'] ?? null, $d['categoria'] ?? null, $d['proveedor'] ?? null),
            'filtroForm' => $form->createView(),
        ]);
    }

    /** Crear nuevo artículo */
    #[Route('/nuevo', name: 'admin_articulo_nuevo')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $articulo = new Articulo();
        $form = $this->createForm(ArticuloType::class, $articulo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->prepareArticulo($articulo, $form->get('imagenFile')->getData());
            $em->persist($articulo);
            $em->flush();
            $this->addFlash('success', 'aviso.articulo.creado');
            return $this->redirectToRoute('admin_articulo_index');
        }

        return $this->render('admin/articulo/form.html.twig', [
            'form'   => $form,
            'titulo' => 'Nuevo artículo',
        ]);
    }

    /** Editar artículo existente */
    #[Route('/{id}/editar', name: 'admin_articulo_editar')]
    public function edit(Articulo $articulo, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ArticuloType::class, $articulo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->prepareArticulo($articulo, $form->get('imagenFile')->getData());
            $em->flush();
            $this->addFlash('success', 'aviso.articulo.actualizado');
            return $this->redirectToRoute('admin_articulo_index');
        }

        return $this->render('admin/articulo/form.html.twig', [
            'form'     => $form,
            'titulo'   => 'Editar artículo',
            'articulo' => $articulo,
        ]);
    }

    /** Eliminar artículo */
    #[Route('/{id}/eliminar', name: 'admin_articulo_eliminar', methods: ['POST'])]
    public function delete(Articulo $articulo, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_articulo_' . $articulo->getId(), $request->request->get('_token'))) {
            $em->remove($articulo);
            $em->flush();
            $this->addFlash('success', 'aviso.articulo.eliminado');
        }
        return $this->redirectToRoute('admin_articulo_index');
    }

    /** Prepara el artículo antes de guardar: calcula precio con IVA y guarda la imagen */
    private function prepareArticulo(Articulo $articulo, ?UploadedFile $imagen): void
    {
        $precioSinIva = (float) $articulo->getPrecioSinIva();
        $iva = (float) $articulo->getIva();
        $precioConIva = $precioSinIva * (1 + ($iva / 100));

        $articulo->setPrecio(number_format($precioConIva, 2, '.', ''));

        if (!$imagen) {
            return;
        }

        $uploadsDir = $this->getParameter('kernel.project_dir').'/public/images';
        $extension = $imagen->guessExtension() ?: $imagen->getClientOriginalExtension() ?: 'bin';
        $filename = uniqid('producto_', true).'.'.$extension;

        $imagen->move($uploadsDir, $filename);
        $articulo->setImagen($filename);
    }
}
