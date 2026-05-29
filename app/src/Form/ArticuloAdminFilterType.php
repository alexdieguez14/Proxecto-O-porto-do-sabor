<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Categoria;
use App\Entity\Proveedor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;

final class ArticuloAdminFilterType extends AbstractFilterType
{
    protected function filterFields(): array
    {
        return [
            [
                'name'    => 'busqueda',
                'type'    => SearchType::class,
                'col'     => 4,
                'options' => [
                    'attr' => ['placeholder' => 'Título o descripción…'],
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
            [
                'name'    => 'proveedor',
                'type'    => EntityType::class,
                'col'     => 3,
                'options' => [
                    'class'        => Proveedor::class,
                    'choice_label' => 'nombre',
                    'placeholder'  => 'Todos los proveedores',
                ],
            ],
        ];
    }
}
