<?php

namespace App\Controller;

use App\Entity\EntradaStock;
use App\Form\InventarioFilterType;
use App\Repository\ArticuloRepository;
use App\Repository\EntradaStockRepository;
use App\Repository\ProveedorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logistica/inventario')]
class InventarioController extends AbstractController
{
    #[Route('', name: 'app_inventario', methods: ['GET'])]
    public function index(
        Request $request,
        ArticuloRepository $articuloRepo,
        ProveedorRepository $proveedorRepo,
        EntradaStockRepository $entradaRepo
    ): Response {
        $form = $this->createForm(InventarioFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('logistica/inventario.html.twig', [
           
            'articulosTodos' => $articuloRepo->findBy([], ['titulo' => 'ASC']),
            'articulos'      => $articuloRepo->buscarFiltrados(
                $d['busqueda']    ?? null,
                $d['categoria']   ?? null,
                $d['proveedor']   ?? null,
                $d['stockEstado'] ?? null,
            ),
            'proveedores'    => $proveedorRepo->findBy([], ['nombre' => 'ASC']),
            'entradas'       => $entradaRepo->buscarRecientes(50),
            'filtroForm'     => $form->createView(),
        ]);
    }

    #[Route('/entrada', name: 'inventario_entrada', methods: ['POST'])]
    public function entrada(
        Request $request,
        ArticuloRepository $articuloRepo,
        ProveedorRepository $proveedorRepo,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('inventario_entrada', $request->request->get('_token'))) {
            $this->addFlash('error', 'aviso.csrf.invalido');
            return $this->redirectToRoute('app_inventario');
        }

        $articuloId  = (int) $request->request->get('articulo_id', 0);
        $cantidad    = (int) $request->request->get('cantidad', 0);
        $proveedorId = (int) $request->request->get('proveedor_id', 0);
        $numAlbaran  = trim((string) $request->request->get('num_albaran', ''));

        if ($articuloId < 1 || $cantidad < 1) {
            $this->addFlash('error', 'aviso.stock.invalido');
            return $this->redirectToRoute('app_inventario');
        }

        $articulo = $articuloRepo->find($articuloId);
        if (!$articulo) {
            $this->addFlash('error', 'aviso.articulo.no_encontrado');
            return $this->redirectToRoute('app_inventario');
        }

        $proveedor = $proveedorId > 0 ? $proveedorRepo->find($proveedorId) : null;

        $articulo->setStock($articulo->getStock() + $cantidad);

        $entrada = new EntradaStock();
        $entrada->setArticulo($articulo)
                ->setProveedor($proveedor)
                ->setCantidad($cantidad)
                ->setNumAlbaran($numAlbaran !== '' ? $numAlbaran : null);

        $em->persist($entrada);
        $em->flush();

        $this->addFlash('success', ['aviso.stock.anadido', [
            '%qty%'   => $cantidad,
            '%title%' => $articulo->getTitulo(),
        ]]);

        return $this->redirectToRoute('app_inventario');
    }
}
