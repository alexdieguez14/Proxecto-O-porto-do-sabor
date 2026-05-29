<?php

namespace App\Repository;

use App\Entity\Proveedor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProveedorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proveedor::class);
    }

    public function buscarFiltrados(?string $busqueda): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.nombre', 'ASC');

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('p.nombre LIKE :q OR p.contacto LIKE :q OR p.email LIKE :q')
               ->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
