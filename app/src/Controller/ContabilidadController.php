<?php

namespace App\Controller;

use App\Entity\MovimientoFinanciero;
use App\Entity\Pedido;
use App\Entity\Usuario;
use App\Form\ClienteFilterType;
use App\Form\EmpleadoFilterType;
use App\Form\MovimientoFilterType;
use App\Form\PedidoContableFilterType;
use App\Repository\ArticuloRepository;
use App\Repository\MovimientoFinancieroRepository;
use App\Repository\PedidoRepository;
use App\Repository\UsuarioRepository;
use App\Service\PublicadorMercure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contabilidad')]
class ContabilidadController extends AbstractController
{
    /* El IVA por defecto se inyecta si el iva no está definido en el articulo */
    public function __construct(
        #[Autowire('%app.iva.defecto%')] private readonly float $ivaDefecto,
    ) {}

    /** Panel principal de contabilidad */
    #[Route('', name: 'app_contabilidad')]
    public function index(
        PedidoRepository $pedidoRepo,
        MovimientoFinancieroRepository $movRepo,
    ): Response {
        return $this->render('contabilidad/index.html.twig', [
            'ingresos'    => $pedidoRepo->totalPagados(),
            'gastos'      => $movRepo->totalPorTipo(MovimientoFinanciero::TIPO_EGRESO),
            'pendientes'  => $pedidoRepo->totalPendientes(),
            'movimientos' => $movRepo->buscarRecientes(10),
        ]);
    }

    /** Listado completo de movimientos financieros */
    #[Route('/movimientos', name: 'contabilidad_movimientos', methods: ['GET'])]
    public function movimientos(Request $request, MovimientoFinancieroRepository $movRepo): Response
    {
        $form = $this->createForm(MovimientoFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('contabilidad/movimientos/index.html.twig', [
            'movimientos' => $movRepo->buscarFiltrados(
                $d['tipo']     ?? null,
                $d['concepto'] ?? null,
                $d['busqueda'] ?? null,
            ),
            'filtroForm' => $form->createView(),
        ]);
    }

    /* Lista de clientes */
    #[Route('/clientes', name: 'contabilidad_cliente_index')]
    public function clientes(Request $request, UsuarioRepository $repo): Response
    {
        $form = $this->createForm(ClienteFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('contabilidad/clientes/index.html.twig', [
            'clientes'   => $repo->buscarClientesFiltrados($d['busqueda'] ?? null),
            'filtroForm' => $form->createView(),
        ]);
    }

    /* Pedidos de un cliente */
    #[Route('/clientes/{id}/pedidos', name: 'contabilidad_cliente_pedidos', methods: ['GET'])]
    public function clientePedidos(Usuario $cliente, PedidoRepository $pedidoRepo): Response
    {
        return $this->render('contabilidad/clientes/pedidos.html.twig', [
            'cliente' => $cliente,
            'pedidos' => $pedidoRepo->buscarPorEmailCliente($cliente->getEmail()),
        ]);
    }

    /* Listado de empleados */
    #[Route('/empleados', name: 'contabilidad_empleado_index')]
    public function empleados(Request $request, UsuarioRepository $repo): Response
    {
        $form = $this->createForm(EmpleadoFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('contabilidad/empleados/index.html.twig', [
            'empleados'  => $repo->buscarEmpleadosFiltrados($d['busqueda'] ?? null, $d['rol'] ?? null),
            'filtroForm' => $form->createView(),
        ]);
    }

    /* Listado de pedidos */
    #[Route('/pedidos', name: 'contabilidad_pedidos')]
    public function pedidos(Request $request, PedidoRepository $repo): Response
    {
        $form = $this->createForm(PedidoContableFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('contabilidad/pedidos/index.html.twig', [
            'pedidos'    => $repo->buscarParaContabilidadFiltrados(
                $d['estadoContable'] ?? null,
                $d['metodoPago']     ?? null,
                $d['busqueda']       ?? null,
            ),
            'filtroForm' => $form->createView(),
        ]);
    }

    /**
     * Marca el pedido como pagado e inyecta automáticamente un movimiento contable.
     * Publica dos actualizaciones Mercure tras el flush:
     *   - pedidos/{id} + pedidos  → los paneles de Logística/Contabilidad actualizan el estado del pedido
     *   - movimientos              → el panel de Contabilidad refresca los totales KPI
     */
    #[Route('/pedidos/{id}/registrar-pago', name: 'contabilidad_registrar_pago', methods: ['POST'])]
    public function registrarPago(
        Pedido $pedido,
        Request $request,
        EntityManagerInterface $em,
        PublicadorMercure $mercure,
    ): Response {
        if (!$this->isCsrfTokenValid('pago_' . $pedido->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        if (in_array($pedido->getEstado(), [Pedido::ESTADO_ENVIADO], true)) {
            $pedido->setEstado(Pedido::ESTADO_PAGADO);
            $pedido->setEstadoContable(Pedido::CONTABLE_PAGADO);

            $nFactura = trim((string) $request->request->get('numero_factura', '')) ?: null;
            $nOpBanc  = trim((string) $request->request->get('numero_operacion_bancaria', '')) ?: null;

            $mov = new MovimientoFinanciero();
            $mov->setTipo(MovimientoFinanciero::TIPO_INGRESO);
            $mov->setConcepto(MovimientoFinanciero::CONCEPTO_PAGO_FACTURA);
            $mov->setImporte($pedido->getTotal());
            $mov->setDescripcion(sprintf('Pedido #%d — %s', $pedido->getId(), $pedido->getClienteEmail()));
            $mov->setNumeroFactura($nFactura);
            $mov->setNumeroOperacionBancaria($nOpBanc);
            $em->persist($mov);

            $em->flush();

            // Publicamos ambas actualizaciones tras el flush para garantizar que los datos estén sincronizados
            $mercure->publicarActualizacionPedido($pedido);
            $mercure->publicarMovimientoCreado($mov);

            $this->addFlash('success', ['aviso.pago.pedido_registrado', ['%id%' => $pedido->getId()]]);
        }

        return $this->redirectToRoute('contabilidad_pedidos');
    }

    /**
     * Registra manualmente un gasto u otro ingreso.
     * Valida que el tipo/concepto sean correctos y que el importe sea positivo.
     */
    #[Route('/movimientos/nuevo', name: 'contabilidad_movimiento_nuevo', methods: ['POST'])]
    public function movimientoNuevo(
        Request $request,
        EntityManagerInterface $em,
        PublicadorMercure $mercure,
    ): Response {
        if (!$this->isCsrfTokenValid('movimiento_nuevo', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }

        $tipo       = $request->request->get('tipo', '');
        $concepto   = $request->request->get('concepto', '');
        $importeRaw = str_replace(',', '.', $request->request->get('importe', '0'));
        $importe    = (float) $importeRaw;
        $descripcion = trim((string) $request->request->get('descripcion', '')) ?: null;
        $nFactura   = trim((string) $request->request->get('numero_factura', '')) ?: null;
        $nOpBanc    = trim((string) $request->request->get('numero_operacion_bancaria', '')) ?: null;

        $tiposValidos    = [MovimientoFinanciero::TIPO_INGRESO, MovimientoFinanciero::TIPO_EGRESO];
        $conceptosEgreso = [
            MovimientoFinanciero::CONCEPTO_NOMINA,
            MovimientoFinanciero::CONCEPTO_PAGO_PROVEEDOR,
            MovimientoFinanciero::CONCEPTO_SUMINISTROS,
            MovimientoFinanciero::CONCEPTO_OTROS,
        ];
        $conceptosIngreso = [MovimientoFinanciero::CONCEPTO_OTROS];
        $conceptosValidos = $tipo === MovimientoFinanciero::TIPO_INGRESO
            ? $conceptosIngreso
            : $conceptosEgreso;

        if (!in_array($tipo, $tiposValidos, true)
            || !in_array($concepto, $conceptosValidos, true)
            || $importe <= 0
        ) {
            $this->addFlash('error', 'aviso.movimiento.invalido');
            return $this->redirectToRoute('app_contabilidad');
        }

        $mov = new MovimientoFinanciero();
        $mov->setTipo($tipo);
        $mov->setConcepto($concepto);
        $mov->setImporte(number_format($importe, 2, '.', ''));
        $mov->setDescripcion($descripcion);
        $mov->setNumeroFactura($nFactura);
        $mov->setNumeroOperacionBancaria($nOpBanc);
        $em->persist($mov);
        $em->flush();

        // Publicamos la actualización tras el flush para garantizar que los datos estén sincronizados
        $mercure->publicarMovimientoCreado($mov);

        $this->addFlash('success', 'aviso.movimiento.registrado');
        return $this->redirectToRoute('app_contabilidad');
    }

    /** Generar factura para un pedido */
    #[Route('/pedidos/{id}/factura', name: 'contabilidad_factura', methods: ['GET'])]
    public function factura(Pedido $pedido, ArticuloRepository $articuloRepo, UsuarioRepository $usuarioRepo): Response
    {
        $usuario = $pedido->getUsuario()
            ?? $usuarioRepo->findOneBy(['email' => $pedido->getClienteEmail()]);

        $items = $pedido->getItems();

        // Calculamos el IVA de cada línea
        $idsNecesarios = [];
        foreach ($items as $item) {
            if (!isset($item['iva']) && isset($item['id'])) {
                $idsNecesarios[] = $item['id'];
            }
        }
        $articuloMap = [];
        foreach ($articuloRepo->buscarPorIds(array_unique($idsNecesarios)) as $a) {
            $articuloMap[$a->getId()] = $a;
        }

        $lineasFactura      = [];
        $desgloseTributario = [];

        foreach ($items as $item) {
            // Si el pedido ya tiene el IVA calculado se usa si no se calcula a partir del artículo o del valor por defecto
            if (isset($item['iva'])) {
                $tipoIva = (float) $item['iva'];
            } else {
                $a = $articuloMap[$item['id']] ?? null;
                $tipoIva = $a ? (float) $a->getIva() : $this->ivaDefecto;
            }

            $subtotalConIva = (float) $item['subtotal'];
            $cantidad       = (int)   $item['cantidad'];
            $divisor        = 1 + $tipoIva / 100;
            $baseLinea      = $subtotalConIva / $divisor;
            $cuotaLinea     = $subtotalConIva - $baseLinea;

            $lineasFactura[] = [
                'titulo'             => $item['titulo'],
                'cantidad'           => $cantidad,
                'tipoIva'            => $tipoIva,
                'precioUnitarioSinIva' => $cantidad > 0 ? $baseLinea / $cantidad : 0.0,
                'base'               => $baseLinea,
                'cuota'              => $cuotaLinea,
                'subtotal'           => $subtotalConIva,
            ];

            // Ponemos el desglose tributario agrupando por tipo de IVA (0%, 10%, 21%, etc)
            $clave = (string) (int) $tipoIva;
            if (!isset($desgloseTributario[$clave])) {
                $desgloseTributario[$clave] = ['tipo' => $tipoIva, 'base' => 0.0, 'cuota' => 0.0];
            }
            $desgloseTributario[$clave]['base']  += $baseLinea;
            $desgloseTributario[$clave]['cuota'] += $cuotaLinea;
        }

        ksort($desgloseTributario);

        return $this->render('contabilidad/factura.html.twig', [
            'pedido'             => $pedido,
            'usuario'            => $usuario,
            'lineasFactura'      => $lineasFactura,
            'desgloseTributario' => $desgloseTributario,
        ]);
    }
}
