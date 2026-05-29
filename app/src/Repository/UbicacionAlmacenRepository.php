<?php

namespace App\Repository;

use App\Entity\UbicacionAlmacen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UbicacionAlmacenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UbicacionAlmacen::class);
    }

    public function buscarFiltrados(?string $busqueda): array
    {
        $qb = $this->createQueryBuilder('u')->orderBy('u.pasillo', 'ASC');

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('u.pasillo LIKE :q OR u.estanteria LIKE :q OR u.nivel LIKE :q')
               ->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
