<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Pedido;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

final class MisPedidosFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name'    => 'estado',
                'type'    => ChoiceType::class,
                'col'     => 4,
                'options' => [
                    'placeholder' => 'Todos los estados',
                    'choices'     => [
                        'En preparación'   => Pedido::ESTADO_PREPARACION,
                        'Listo para envío' => Pedido::ESTADO_LISTO_ENVIO,
                        'Enviado'          => Pedido::ESTADO_ENVIADO,
                        'Completado'       => Pedido::ESTADO_PAGADO,
                    ],
                ],
            ],
            [
                'name'    => 'metodoPago',
                'type'    => ChoiceType::class,
                'col'     => 4,
                'options' => [
                    'placeholder' => 'Todos los métodos',
                    'choices'     => [
                        'Tarjeta'          => Pedido::METODO_TARJETA,
                        'Transferencia'    => Pedido::METODO_TRANSFERENCIA,
                        'Contrarreembolso' => Pedido::METODO_CONTRARREEMBOLSO,
                    ],
                ],
            ],
        ];
    }
}
