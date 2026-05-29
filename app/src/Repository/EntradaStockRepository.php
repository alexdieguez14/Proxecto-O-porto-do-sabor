<?php

namespace App\Repository;

use App\Entity\EntradaStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntradaStockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntradaStock::class);
    }

    /** Devuelve las 50 entradas de stock más recientes. */
    public function buscarRecientes(int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.articulo', 'a')
            ->leftJoin('e.proveedor', 'p')
            ->addSelect('a', 'p')
            ->orderBy('e.creadoEn', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
