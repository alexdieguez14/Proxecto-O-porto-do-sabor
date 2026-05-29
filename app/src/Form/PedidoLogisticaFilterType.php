<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Pedido;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;

final class PedidoLogisticaFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name'    => 'metodoPago',
                'type'    => ChoiceType::class,
                'col'     => 3,
                'options' => [
                    'placeholder' => 'Todos los métodos',
                    'choices'     => [
                        'Tarjeta'           => Pedido::METODO_TARJETA,
                        'Transferencia'     => Pedido::METODO_TRANSFERENCIA,
                        'Contrarreembolso'  => Pedido::METODO_CONTRARREEMBOLSO,
                    ],
                ],
            ],
            [
                'name'    => 'busqueda',
                'type'    => SearchType::class,
                'col'     => 5,
                'options' => [
                    'attr' => ['placeholder' => 'Email del cliente…'],
                ],
            ],
        ];
    }
}
