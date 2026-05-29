<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\MovimientoFinanciero;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;

final class MovimientoFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name' => 'tipo',
                'type' => ChoiceType::class,
                'col'  => 3,
                'options' => [
                    'placeholder' => 'Todos los tipos',
                    'choices' => [
                        'Ingreso' => MovimientoFinanciero::TIPO_INGRESO,
                        'Egreso'  => MovimientoFinanciero::TIPO_EGRESO,
                    ],
                ],
            ],
            [
                'name' => 'concepto',
                'type' => ChoiceType::class,
                'col'  => 3,
                'options' => [
                    'placeholder' => 'Todos los conceptos',
                    'choices' => [
                        'Pago de factura'   => MovimientoFinanciero::CONCEPTO_PAGO_FACTURA,
                        'Nómina'            => MovimientoFinanciero::CONCEPTO_NOMINA,
                        'Pago a proveedor'  => MovimientoFinanciero::CONCEPTO_PAGO_PROVEEDOR,
                        'Suministros'       => MovimientoFinanciero::CONCEPTO_SUMINISTROS,
                        'Otros'             => MovimientoFinanciero::CONCEPTO_OTROS,
                    ],
                ],
            ],
            [
                'name' => 'busqueda',
                'type' => SearchType::class,
                'col'  => 4,
                'options' => [
                    'attr' => ['placeholder' => 'Referencia o descripción…'],
                ],
            ],
        ];
    }
}
