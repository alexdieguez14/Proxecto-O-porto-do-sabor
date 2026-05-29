<?php

namespace App\Controller;

use App\Entity\MovimientoFinanciero;
use App\Entity\Pedido;
use App\Form\PedidoLogisticaFilterType;
use App\Repository\ArticuloRepository;
use App\Repository\PedidoRepository;
use App\Service\PublicadorMercure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/logistica')]
class LogisticaController extends AbstractController
{
    #[Route('', name: 'app_logistica', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('logistica_preparacion');
    }

    #[Route('/preparacion', name: 'logistica_preparacion', methods: ['GET'])]
    public function preparacion(Request $request, PedidoRepository $repo, ArticuloRepository $articuloRepo): Response
    {
        return $this->renderizarPedidos($request, $repo, $articuloRepo, Pedido::ESTADO_PREPARACION, 'preparacion');
    }

    #[Route('/listos', name: 'logistica_listos', methods: ['GET'])]
    public function listos(Request $request, PedidoRepository $repo, ArticuloRepository $articuloRepo): Response
    {
        return $this->renderizarPedidos($request, $repo, $articuloRepo, Pedido::ESTADO_LISTO_ENVIO, 'listos');
    }

    #[Route('/enviados', name: 'logistica_enviados', methods: ['GET'])]
    public function enviados(Request $request, PedidoRepository $repo, ArticuloRepository $articuloRepo): Response
    {
        return $this->renderizarPedidos($request, $repo, $articuloRepo, Pedido::ESTADO_ENVIADO, 'enviados');
    }

    private function renderizarPedidos(
        Request $request,
        PedidoRepository $repo,
        ArticuloRepository $articuloRepo,
        string $estado,
        string $activeSection
    ): Response
    {
        $form = $this->createForm(PedidoLogisticaFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        $pedidos = $repo->buscarParaLogisticaFiltrados($d['metodoPago'] ?? null, $d['busqueda'] ?? null, $estado);

        $idsNecesarios = [];
        foreach ($pedidos as $pedido) {
            foreach ($pedido->getItems() as $item) {
                if (empty($item['ubicacion']) && isset($item['id'])) {
                    $idsNecesarios[] = $item['id'];
                }
            }
        }

        $ubicaciones = [];
        foreach ($articuloRepo->buscarConUbicacionPorIds(array_unique($idsNecesarios)) as $articulo) {
            $ubicaciones[$articulo->getId()] = (string) ($articulo->getUbicacion() ?? '');
        }

        return $this->render('logistica/index.html.twig', [
            'pedidos'       => $pedidos,
            'ubicaciones'   => $ubicaciones,
            'filtroForm'    => $form->createView(),
            'activeSection' => $activeSection,
        ]);
    }

    // Cambio de preparación a listo para envío
    #[Route('/{id}/marcar-listo', name: 'logistica_marcar_listo', methods: ['POST'])]
    public function marcarListo(
        Pedido $pedido,
        Request $request,
        EntityManagerInterface $em,
        PublicadorMercure $mercure,
    ): Response {
        $this->validarCsrf($pedido, $request);

        if ($pedido->getEstado() === Pedido::ESTADO_PREPARACION) {
            $pedido->setEstado(Pedido::ESTADO_LISTO_ENVIO);
            $em->flush();
            $mercure->publicarActualizacionPedido($pedido);
            $this->addFlash('success', ['aviso.pedido.listo', ['%id%' => $pedido->getId()]]);
        }

        return $this->redirectToRoute('logistica_preparacion');
    }

    /** 
     * Cambio de listo para envío a enviado
     * Pasa el pedido a estado "enviado" en el panel de contabilidad mediante mercure
     */
    #[Route('/{id}/marcar-enviado', name: 'logistica_marcar_enviado', methods: ['POST'])]
    public function marcarEnviado(
        Pedido $pedido,
        Request $request,
        EntityManagerInterface $em,
        PublicadorMercure $mercure,
    ): Response {
        $this->validarCsrf($pedido, $request);

        if ($pedido->getEstado() === Pedido::ESTADO_LISTO_ENVIO) {
            $pedido->setEstado(Pedido::ESTADO_ENVIADO);
            $pedido->setEnviadoEn(new \DateTimeImmutable());

            $numeroSeguimiento = trim((string) $request->request->get('numero_seguimiento', ''));
            $pedido->setNumeroSeguimiento($numeroSeguimiento !== '' ? $numeroSeguimiento : null);

            if ($pedido->getMetodoPago() === Pedido::METODO_TARJETA) {
                $pedido->setEstadoContable(Pedido::CONTABLE_PAGADO);

                $mov = new MovimientoFinanciero();
                $mov->setTipo(MovimientoFinanciero::TIPO_INGRESO);
                $mov->setConcepto(MovimientoFinanciero::CONCEPTO_PAGO_FACTURA);
                $mov->setImporte($pedido->getTotal());
                $mov->setDescripcion(sprintf('Pedido #%d — %s', $pedido->getId(), $pedido->getClienteEmail()));
                $em->persist($mov);
            } else {
                $pedido->setEstadoContable(Pedido::CONTABLE_PENDIENTE);
            }

            $em->flush();

            $mercure->publicarActualizacionPedido($pedido);
            if (isset($mov)) {
                $mercure->publicarMovimientoCreado($mov);
            }

            $this->addFlash('success', ['aviso.pedido.enviado', ['%id%' => $pedido->getId()]]);
        }

        return $this->redirectToRoute('logistica_enviados');
    }

    /** Actualiza el número de seguimiento de un pedido ya registrado. */
    #[Route('/{id}/seguimiento', name: 'logistica_guardar_seguimiento', methods: ['POST'])]
    public function guardarSeguimiento(Pedido $pedido, Request $request, EntityManagerInterface $em): Response
    {
        $this->validarCsrf($pedido, $request);

        $numero = trim((string) $request->request->get('numero_seguimiento', ''));
        $pedido->setNumeroSeguimiento($numero !== '' ? $numero : null);
        $em->flush();

        $this->addFlash('success', ['aviso.pedido.seguimiento_actualizado', ['%id%' => $pedido->getId()]]);
        return $this->redirectToRoute('logistica_enviados');
    }


    /** Guarda el número de bultos para pedidos en preparacion para el albarán */
    #[Route('/{id}/bultos', name: 'logistica_bultos', methods: ['POST'])]
    public function actualizarBultos(Pedido $pedido, Request $request, EntityManagerInterface $em): Response
    {
        $this->validarCsrf($pedido, $request);

        $bultos = (int) $request->request->get('bultos', 0);
        $pedido->setBultos($bultos > 0 ? $bultos : null);
        $em->flush();

        return $this->redirectToRoute('logistica_preparacion');
    }

    /** Muestra el albarán imprimible para un pedido */
    #[Route('/{id}/albaran', name: 'logistica_albaran', methods: ['GET'])]
    public function albaran(Pedido $pedido): Response
    {
        return $this->render('logistica/albaran.html.twig', [
            'pedido' => $pedido,
        ]);
    }
    
    // Validación CSRF para las acciones de logística
    private function validarCsrf(Pedido $pedido, Request $request): void
    {
        if (!$this->isCsrfTokenValid('logistica_' . $pedido->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }
    }
}
