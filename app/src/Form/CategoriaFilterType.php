<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\Extension\Core\Type\SearchType;

final class CategoriaFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name'    => 'busqueda',
                'type'    => SearchType::class,
                'col'     => 5,
                'options' => [
                    'attr' => ['placeholder' => 'Buscar por nombre…'],
                ],
            ],
        ];
    }
}
