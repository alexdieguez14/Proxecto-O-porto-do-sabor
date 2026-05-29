<?php

namespace App\Controller;

use App\Entity\Articulo;
use App\Entity\Direccion;
use App\Entity\MetodoPagoGuardado;
use App\Entity\Pedido;
use App\Entity\Usuario;
use App\Form\ArticuloFilterType;
use App\Repository\ArticuloRepository;
use App\Repository\DireccionRepository;
use App\Repository\MetodoPagoGuardadoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClienteController extends AbstractController
{
    #[Route('/cliente', name: 'app_cliente', methods: ['GET'])]
    public function cliente(): RedirectResponse
    {
        return $this->redirectToRoute('app_tienda');
    }

    #[Route('/tienda', name: 'app_tienda', methods: ['GET'])]
    public function index(
        Request $request,
        ArticuloRepository $articuloRepository
    ): Response {
        $form = $this->createForm(ArticuloFilterType::class);
        $form->handleRequest($request);

        $busqueda  = null;
        $categoria = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $busqueda  = $form->get('busqueda')->getData();
            $categoria = $form->get('categoria')->getData();
        }

        $articulos  = $articuloRepository->buscarFiltrados($busqueda, $categoria);
        $categories = $this->agruparPorCategoria($articulos, $request->getLocale());

        return $this->render('cliente/index.html.twig', [
            'categories' => $categories,
            'filtroForm' => $form->createView(),
        ]);
    }

    /** Devuelve en JSON las direcciones y métodos de pago guardados del usuario autenticado. */
    #[Route('/tienda/checkout/data', name: 'app_tienda_checkout_data', methods: ['GET'])]
    public function datosCheckout(
        DireccionRepository $dirRepo,
        MetodoPagoGuardadoRepository $mpRepo
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof Usuario) {
            return $this->json(['ok' => false, 'redirectUrl' => $this->generateUrl('app_login')], 401);
        }

        $direcciones = $dirRepo->findBy(['usuario' => $user], ['alias' => 'ASC']);
        $metodosPago = $mpRepo->findBy(['usuario' => $user], ['alias' => 'ASC']);

        return $this->json([
            'ok' => true,
            'direcciones' => array_map(fn(Direccion $d) => [
                'id'           => $d->getId(),
                'alias'        => $d->getAlias() ?? $d->getCalle(),
                'calle'        => $d->getCalle(),
                'ciudad'       => $d->getCiudad(),
                'codigoPostal' => $d->getCodigoPostal(),
                'provincia'    => $d->getProvincia(),
                'pais'         => $d->getPais(),
                'tipo'         => $d->getTipo(),
            ], $direcciones),
            'metodosPago' => array_map(fn(MetodoPagoGuardado $m) => [
                'id'            => $m->getId(),
                'tipo'          => $m->getTipo(),
                'alias'         => $m->getAlias(),
                'detalleMasked' => $m->getDetalleMasked(),
            ], $metodosPago),
        ]);
    }

    // Guarda una nueva dirección para el usuario autenticado y la devuelve.
    #[Route('/tienda/addresses', name: 'app_tienda_save_address', methods: ['POST'])]
    public function guardarDireccion(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Usuario) {
            return $this->json(['ok' => false], 401);
        }

        if (!$this->isCsrfTokenValid('checkout', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['ok' => false, 'message' => 'Token inválido.'], 419);
        }

        $p = json_decode((string) $request->getContent(), true) ?? [];

        $calle  = trim((string) ($p['calle'] ?? ''));
        $ciudad = trim((string) ($p['ciudad'] ?? ''));
        $cp     = trim((string) ($p['codigoPostal'] ?? ''));

        if ($calle === '' || $ciudad === '' || $cp === '') {
            return $this->json(['ok' => false, 'message' => 'Calle, ciudad y código postal son obligatorios.'], 400);
        }

        $tipo = in_array($p['tipo'] ?? '', [Direccion::TIPO_ENVIO, Direccion::TIPO_FACTURACION, Direccion::TIPO_AMBAS], true)
            ? $p['tipo']
            : Direccion::TIPO_AMBAS;

        $dir = new Direccion();
        $dir->setUsuario($user)
            ->setCalle($calle)
            ->setCiudad($ciudad)
            ->setCodigoPostal($cp)
            ->setProvincia(trim((string) ($p['provincia'] ?? '')))
            ->setPais(trim((string) ($p['pais'] ?? '')) ?: 'España')
            ->setTipo($tipo)
            ->setAlias(trim((string) ($p['alias'] ?? '')) ?: null);

        $em->persist($dir);
        $em->flush();

        return $this->json([
            'ok'           => true,
            'id'           => $dir->getId(),
            'alias'        => $dir->getAlias() ?? $dir->getCalle(),
            'calle'        => $dir->getCalle(),
            'ciudad'       => $dir->getCiudad(),
            'codigoPostal' => $dir->getCodigoPostal(),
            'provincia'    => $dir->getProvincia(),
            'pais'         => $dir->getPais(),
            'tipo'         => $dir->getTipo(),
        ]);
    }

    /**
     * Guarda un método de pago enmascarado para el usuario autenticado.
     * 
     */
    #[Route('/tienda/payment-methods', name: 'app_tienda_save_payment_method', methods: ['POST'])]
    public function guardarMetodoPago(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Usuario) {
            return $this->json(['ok' => false], 401);
        }

        if (!$this->isCsrfTokenValid('checkout', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['ok' => false, 'message' => 'Token inválido.'], 419);
        }

        $p    = json_decode((string) $request->getContent(), true) ?? [];
        $tipo = $p['tipo'] ?? '';

        if (!in_array($tipo, [MetodoPagoGuardado::TIPO_TARJETA, MetodoPagoGuardado::TIPO_CUENTA_BANCARIA], true)) {
            return $this->json(['ok' => false, 'message' => 'Tipo de método de pago no válido.'], 400);
        }

        $detalle = trim((string) ($p['detalleMasked'] ?? ''));
        if ($detalle === '') {
            return $this->json(['ok' => false, 'message' => 'Debes proporcionar el detalle enmascarado.'], 400);
        }

        $mp = new MetodoPagoGuardado();
        $mp->setUsuario($user)
           ->setTipo($tipo)
           ->setAlias(trim((string) ($p['alias'] ?? '')) ?: ($tipo === MetodoPagoGuardado::TIPO_TARJETA ? 'Mi tarjeta' : 'Mi cuenta'))
           ->setDetalleMasked($detalle)
           ->setTokenMeta($p['tokenMeta'] ?? null);

        $em->persist($mp);
        $em->flush();

        return $this->json([
            'ok'            => true,
            'id'            => $mp->getId(),
            'tipo'          => $mp->getTipo(),
            'alias'         => $mp->getAlias(),
            'detalleMasked' => $mp->getDetalleMasked(),
        ]);
    }

    #[Route('/tienda/pedido/crear', name: 'app_tienda_pedido_crear', methods: ['POST'])]
    public function crearPedido(
        Request $request,
        ArticuloRepository $articuloRepository,
        DireccionRepository $dirRepo,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->getUser()) {
            return $this->json(['ok' => false, 'redirectUrl' => $this->generateUrl('app_login'), 'message' => 'Debes iniciar sesión.'], 401);
        }

        if (!$this->isCsrfTokenValid('place_order', (string) $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(['ok' => false, 'message' => 'Token inválido.'], 419);
        }

        $payload = json_decode((string) $request->getContent(), true) ?? [];
        $items   = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        if ($items === []) {
            return $this->json(['ok' => false, 'message' => 'La cesta está vacía.'], 400);
        }

        $normalizedItems = [];
        $total = 0.0;

        foreach ($items as $item) {
            $id       = (int) ($item['id'] ?? 0);
            $quantity = (int) ($item['cantidad'] ?? $item['quantity'] ?? 0);
            if ($id < 1 || $quantity < 1) { continue; }

            $articulo = $articuloRepository->find($id);
            if (!$articulo instanceof Articulo) { continue; }

            $unitPrice = (float) $articulo->getPrecio();
            $subtotal  = $unitPrice * $quantity;
            $total    += $subtotal;

            $normalizedItems[] = [
                'id'            => $articulo->getId(),
                'titulo'        => $articulo->getTitulo(),
                'cantidad'      => $quantity,
                'precioUnitario'=> $unitPrice,
                'subtotal'      => $subtotal,
            ];
        }

        if ($normalizedItems === []) {
            return $this->json(['ok' => false, 'message' => 'No se han encontrado productos válidos.'], 400);
        }

        $usuario = $this->getUser();
        if (!$usuario instanceof Usuario) {
            return $this->json(['ok' => false, 'redirectUrl' => $this->generateUrl('app_login'), 'message' => 'Debes iniciar sesión.'], 401);
        }

        // Método de pago
        $validMethods = [Pedido::METODO_TARJETA, Pedido::METODO_TRANSFERENCIA, Pedido::METODO_CONTRARREEMBOLSO];
        $rawMetodo    = $payload['metodoPago'] ?? '';
        $metodoPago   = in_array($rawMetodo, $validMethods, true) ? $rawMetodo : Pedido::METODO_TRANSFERENCIA;

        //Instantáneas de dirección 
        $shippingSnapshot = $this->resolverInstantaneaDireccion($payload['shippingAddress'] ?? null, $usuario, $dirRepo);
        $billingRaw       = $payload['billingAddress'] ?? null;
        $billingSnapshot  = ($billingRaw !== null)
            ? $this->resolverInstantaneaDireccion($billingRaw, $usuario, $dirRepo)
            : $shippingSnapshot;

        $cantidadTotal = (int) array_sum(array_column($normalizedItems, 'cantidad'));

        //Descontar stock por cada artículo pedido
        foreach ($normalizedItems as $item) {
            $articulo = $articuloRepository->find($item['id']);
            if ($articulo) {
                $newStock = max(0, $articulo->getStock() - $item['cantidad']);
                $articulo->setStock($newStock);
            }
        }

        $pedido = new Pedido();
        $pedido->setUsuario($usuario)
               ->setClienteEmail($usuario->getUserIdentifier())
               ->setEstado(Pedido::ESTADO_PREPARACION)
               ->setItems($normalizedItems)
               ->setTotal(number_format($total, 2, '.', ''))
               ->setCantidadTotal($cantidadTotal)
               ->setMetodoPago($metodoPago)
               ->setDireccionEnvio($shippingSnapshot)
               ->setDireccionFacturacion($billingSnapshot);

        $entityManager->persist($pedido);
        $entityManager->flush();

        return $this->json(['ok' => true, 'message' => 'Pedido enviado a logística.', 'pedidoId' => $pedido->getId()]);
    }

    // ── Métodos auxiliares ─────────────────────────────────────────────────

    /**
     * Resuelve un payload de dirección (ID existente o campos en línea) a un array de instantánea.
     * Devuelve null si no hay nada utilizable.
     *
     * @param mixed $raw
     */
    private function resolverInstantaneaDireccion($raw, Usuario $user, DireccionRepository $dirRepo): ?array
    {
        if ($raw === null) {
            return null;
        }

        // ID numérico → cargar de BD y verificar propiedad
        if (is_int($raw) || (is_string($raw) && ctype_digit($raw))) {
            $dir = $dirRepo->find((int) $raw);
            if ($dir instanceof Direccion && $dir->getUsuario()->getId() === $user->getId()) {
                return $dir->comoInstantanea();
            }
            return null;
        }

        // Objeto de dirección en línea
        if (is_array($raw)) {
            $calle = trim((string) ($raw['calle'] ?? ''));
            if ($calle === '') {
                return null;
            }
            return [
                'calle'        => $calle,
                'ciudad'       => trim((string) ($raw['ciudad'] ?? '')),
                'codigoPostal' => trim((string) ($raw['codigoPostal'] ?? '')),
                'provincia'    => trim((string) ($raw['provincia'] ?? '')),
                'pais'         => trim((string) ($raw['pais'] ?? '')) ?: 'España',
                'alias'        => trim((string) ($raw['alias'] ?? '')) ?: null,
            ];
        }

        return null;
    }

    private function agruparPorCategoria(array $articulos, string $locale = 'es'): array
    {
        $map = [];

        foreach ($articulos as $articulo) {
            $cat = $articulo->getCategoria();
            if ($cat !== null) {
                $id = $cat->getId();
                if (!isset($map[$id])) {
                    $title = $cat->getNombreLocalizado($locale) ?: 'categoria';
                    $map[$id] = [
                        'id'       => $id,
                        'slug'     => $this->generarSlug($title.'-'.$id),
                        'title'    => $title,
                        'products' => [],
                    ];
                }
                $map[$id]['products'][] = $articulo;
            } else {
                if (!isset($map['sin-categoria'])) {
                    $map['sin-categoria'] = [
                        'id'       => null,
                        'slug'     => 'sin-categoria',
                        'title'    => 'Sin categoría',
                        'products' => [],
                    ];
                }
                $map['sin-categoria']['products'][] = $articulo;
            }
        }

        usort($map, fn($a, $b) => ($a['id'] === null ? 1 : 0) - ($b['id'] === null ? 1 : 0)
            ?: strcmp((string) $a['title'], (string) $b['title']));

        return array_values($map);
    }

    private function generarSlug(string $value): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value), '-'));
        return $slug !== '' ? $slug : 'categoria';
    }
}
