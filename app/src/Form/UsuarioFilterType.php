<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;

/**
 * Filter form for the Usuario list.
 * Entire configuration lives in filterFields() — nothing else to implement.
 */
final class UsuarioFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name'    => 'busqueda',
                'type'    => SearchType::class,
                'col'     => 5,
                'options' => [
                    'attr' => ['placeholder' => 'Nombre, email…'],
                ],
            ],
            [
                'name'    => 'rol',
                'type'    => ChoiceType::class,
                'col'     => 3,
                'options' => [
                    'placeholder' => 'Todos los roles',
                    'choices'     => [
                        'Administrador' => 'ROLE_ADMIN',
                        'Empleado'      => 'ROLE_EMPLEADO',
                        'Cliente'       => 'ROLE_CLIENTE',
                    ],
                ],
            ],
        ];
    }
}
