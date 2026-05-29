<?php

namespace App\Repository;

use App\Entity\MetodoPagoGuardado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MetodoPagoGuardadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetodoPagoGuardado::class);
    }
}
