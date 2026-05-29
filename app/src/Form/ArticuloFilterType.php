<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Categoria;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;

final class ArticuloFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name'    => 'busqueda',
                'type'    => SearchType::class,
                'col'     => 5,
                'options' => [
                    'attr' => ['placeholder' => 'Buscar por título o descripción…'],
                ],
            ],
            [
                'name'    => 'categoria',
                'type'    => EntityType::class,
                'col'     => 3,
                'options' => [
                    'class'        => Categoria::class,
                    'choice_label' => 'nombre',
                    'placeholder'  => 'Todas las categorías',
                ],
            ],
        ];
    }
}
