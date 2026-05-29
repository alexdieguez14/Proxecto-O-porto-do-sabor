<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Pedido;
use App\Entity\Usuario;
use App\Form\MisPedidosFilterType;
use App\Repository\ArticuloRepository;
use App\Repository\PedidoRepository;
use App\Repository\UsuarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mi-cuenta')]
class MiCuentaController extends AbstractController
{
    /** Inyección del IVA que se configura en .env aunque el articulo no lo tenga */
    public function __construct(
        #[Autowire('%app.iva.defecto%')] private readonly float $ivaDefecto,
    ) {}

    /** Listado de pedidos de un usuario */
    #[Route('/pedidos', name: 'mi_cuenta_pedidos', methods: ['GET'])]
    public function pedidos(Request $request, PedidoRepository $pedidoRepo): Response
    {
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        $form = $this->createForm(MisPedidosFilterType::class);
        $form->handleRequest($request);

        $d = ($form->isSubmitted() && $form->isValid()) ? $form->getData() : [];

        return $this->render('mi_cuenta/pedidos.html.twig', [
            'pedidos'    => $pedidoRepo->buscarPorEmailClienteFiltrados(
                $usuario->getUserIdentifier(),
                $d['estado']     ?? null,
                $d['metodoPago'] ?? null,
            ),
            'filtroForm' => $form->createView(),
        ]);
    }

    /** Factura descargable filtrada por usuario */
    #[Route('/pedidos/{id}/factura', name: 'mi_cuenta_factura', methods: ['GET'])]
    public function factura(Pedido $pedido, ArticuloRepository $articuloRepo, UsuarioRepository $usuarioRepo): Response
    {
        /** @var Usuario $usuarioLogado */
        $usuarioLogado = $this->getUser();

        if ($pedido->getClienteEmail() !== $usuarioLogado->getUserIdentifier()) {
            throw $this->createAccessDeniedException();
        }

        $usuario = $pedido->getUsuario()
            ?? $usuarioRepo->findOneBy(['email' => $pedido->getClienteEmail()]);

        $items = $pedido->getItems();

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
                'titulo'               => $item['titulo'],
                'cantidad'             => $cantidad,
                'tipoIva'              => $tipoIva,
                'precioUnitarioSinIva' => $cantidad > 0 ? $baseLinea / $cantidad : 0.0,
                'base'                 => $baseLinea,
                'cuota'                => $cuotaLinea,
                'subtotal'             => $subtotalConIva,
            ];

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
