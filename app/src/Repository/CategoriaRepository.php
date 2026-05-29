<?php

namespace App\Repository;

use App\Entity\Categoria;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoriaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categoria::class);
    }

    public function buscarFiltrados(?string $busqueda): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.nombre', 'ASC');

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('c.nombre LIKE :q')->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
