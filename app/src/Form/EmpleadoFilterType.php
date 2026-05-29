<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;

final class EmpleadoFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name'    => 'busqueda',
                'type'    => SearchType::class,
                'col'     => 4,
                'options' => [
                    'attr' => ['placeholder' => 'Nombre, apellidos o email…'],
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
                        'Logística'     => 'ROLE_LOGISTICA',
                        'Contabilidad'  => 'ROLE_CONTABILIDAD',
                    ],
                ],
            ],
        ];
    }
}
