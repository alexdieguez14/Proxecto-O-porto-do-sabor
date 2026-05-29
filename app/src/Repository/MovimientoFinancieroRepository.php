<?php

namespace App\Repository;

use App\Entity\MovimientoFinanciero;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MovimientoFinancieroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovimientoFinanciero::class);
    }

    public function totalPorTipo(string $tipo): float
    {
        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.importe)')
            ->where('m.tipo = :tipo')
            ->setParameter('tipo', $tipo)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    /** Suma de movimientos de un tipo en el mes indicado. */
    public function totalPorTipoMes(string $tipo, ?\DateTimeImmutable $mes = null): float
    {
        $mes   = $mes ?? new \DateTimeImmutable();
        $inicio = new \DateTimeImmutable($mes->format('Y-m-01 00:00:00'));
        $fin    = $inicio->modify('+1 month');

        $result = $this->createQueryBuilder('m')
            ->select('SUM(m.importe)')
            ->where('m.tipo = :tipo')
            ->andWhere('m.creadoEn >= :inicio')
            ->andWhere('m.creadoEn < :fin')
            ->setParameter('tipo', $tipo)
            ->setParameter('inicio', $inicio)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    public function buscarRecientes(int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.creadoEn', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function buscarTodos(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function buscarFiltrados(?string $tipo, ?string $concepto, ?string $busqueda): array
    {
        $qb = $this->createQueryBuilder('m');

        if ($tipo) {
            $qb->andWhere('m.tipo = :tipo')->setParameter('tipo', $tipo);
        }

        if ($concepto) {
            $qb->andWhere('m.concepto = :concepto')->setParameter('concepto', $concepto);
        }

        if ($busqueda) {
            $qb->andWhere('m.numeroFactura LIKE :busqueda OR m.descripcion LIKE :busqueda')
               ->setParameter('busqueda', '%' . $busqueda . '%');
        }

        return $qb->orderBy('m.creadoEn', 'DESC')->getQuery()->getResult();
    }
}
