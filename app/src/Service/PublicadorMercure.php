<?php

namespace App\Service;

use App\Entity\MovimientoFinanciero;
use App\Entity\Pedido;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publica actualizaciones SSE en el hub Mercure cuando se cruza el límite
 * entre Logística y Contabilidad (pedido enviado, pago registrado, asiento contable creado).
 *
 * Convención de nombres de topics (pluralizados):
 *   https://portodosabor.local/topics/pedidos        — canal global de pedidos
 *   https://portodosabor.local/topics/pedidos/{id}   — canal de un pedido concreto
 *   https://portodosabor.local/topics/movimientos     — canal del libro contable
 */
class PublicadorMercure
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.mercure.base_topic%')] private readonly string $baseTopic,
    ) {}

    /**
     * Publica cuando cambia el estado de un Pedido (p. ej. LISTO_ENVIO → ENVIADO,
     * o ENVIADO → PAGADO). Notifica tanto al topic individual como al canal global
     * para que cualquier panel suscrito actualice su vista.
     */
    public function publicarActualizacionPedido(Pedido $pedido): void
    {
        $this->publicar(
            topics: [
                $this->baseTopic . '/pedidos/' . $pedido->getId(),
                $this->baseTopic . '/pedidos',
            ],
            data: [
                'evento'         => 'pedido.actualizado',
                'id'             => $pedido->getId(),
                'estado'         => $pedido->getEstado(),
                'estadoContable' => $pedido->getEstadoContable(),
                'clienteEmail'   => $pedido->getClienteEmail(),
                'total'          => $pedido->getTotal(),
                'metodoPago'     => $pedido->getMetodoPago(),
            ]
        );
    }

    /**
     * Publica cuando se persiste un nuevo MovimientoFinanciero (inyectado automáticamente
     * al registrar un pago o desde el formulario manual de asientos). Notifica al canal
     * del libro contable para que el panel de Contabilidad actualice sus totales KPI.
     */
    public function publicarMovimientoCreado(MovimientoFinanciero $mov): void
    {
        $this->publicar(
            topics: [$this->baseTopic . '/movimientos'],
            data: [
                'evento'    => 'movimiento.creado',
                'id'        => $mov->getId(),
                'tipo'      => $mov->getTipo(),
                'concepto'  => $mov->getConcepto(),
                'importe'   => $mov->getImporte(),
                'creadoEn'  => $mov->getCreadoEn()->format('Y-m-d H:i:s'),
            ]
        );
    }

    /** @param string[] $topics */
    private function publicar(array $topics, array $data): void
    {
        try {
            $this->hub->publish(new Update($topics, json_encode($data, JSON_THROW_ON_ERROR)));
        } catch (\Throwable $e) {
            // Nunca dejar que un fallo de Mercure interrumpa el flujo HTTP principal
            $this->logger->error('Error al publicar en Mercure: {msg}', [
                'msg'    => $e->getMessage(),
                'topics' => $topics,
            ]);
        }
    }
}
