<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Articulo;
use App\Entity\Categoria;
use App\Entity\Proveedor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ArticuloRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        #[Autowire('%app.stock.umbral_bajo%')] private readonly int $stockUmbralBajo = 5,
    ) {
        parent::__construct($registry, Articulo::class);
    }

    /**
     * Decrementa stock de forma atómica mediante un UPDATE directo que sólo actúa
     * si stock >= cantidad pedida. Devuelve el número de filas afectadas:
     *   1 → éxito
     *   0 → stock insuficiente en ese instante (otro hilo lo agotó entre la lectura y este UPDATE)
     *
     * Al no pasar por el Unit of Work de Doctrine se evita cualquier condición de carrera.
     */
    public function decrementarStockAtomico(int $articuloId, int $cantidad): int
    {
        return (int) $this->getEntityManager()
            ->createQuery(
                'UPDATE App\Entity\Articulo a
                 SET a.stock = a.stock - :qty
                 WHERE a.id = :id AND a.stock >= :qty'
            )
            ->setParameter('qty', $cantidad)
            ->setParameter('id', $articuloId)
            ->execute();
    }

    /**
     * Carga todos los artículos con su categoría en una sola consulta JOIN,
     * eliminando el patrón N+1 que existía en getProductsByCategory().
     * Filtros opcionales: texto libre (título / descripción) y categoría exacta.
     * El resultado está ordenado por nombre de categoría y luego por título de artículo,
     * lo que permite agruparlos en PHP sin consultas adicionales.
     */
    /**
     * @param string|null $stockEstado  'sin_stock' | 'bajo' (1–5 units) | 'disponible' (>5) | null = no filter
     */
    public function buscarFiltrados(
        ?string $busqueda,
        ?Categoria $categoria,
        ?Proveedor $proveedor = null,
        ?string $stockEstado = null
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.categoria', 'cat')
            ->addSelect('cat')
            ->orderBy('cat.nombre', 'ASC')
            ->addOrderBy('a.titulo', 'ASC');

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('a.titulo LIKE :busqueda OR a.descripcion LIKE :busqueda')
                ->setParameter('busqueda', '%' . $busqueda . '%');
        }

        if ($categoria !== null) {
            $qb->andWhere('a.categoria = :categoria')
                ->setParameter('categoria', $categoria);
        }

        if ($proveedor !== null) {
            $qb->andWhere('a.proveedor = :proveedor')
                ->setParameter('proveedor', $proveedor);
        }

        match ($stockEstado) {
            'sin_stock' => $qb->andWhere('a.stock = 0'),
            'bajo' => $qb->andWhere('a.stock > 0')->andWhere('a.stock <= :umbral')->setParameter('umbral', $this->stockUmbralBajo),
            'disponible' => $qb->andWhere('a.stock > :umbral')->setParameter('umbral', $this->stockUmbralBajo),
            default => null,
        };

        return $qb->getQuery()->getResult();
    }

    /** Carga artículos por IDs (sin joins extra). Usado para leer iva en factura. */
    public function buscarPorIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /** Carga artículos por IDs con su ubicación en un solo JOIN, evitando N+1. */
    public function buscarConUbicacionPorIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->leftJoin('a.ubicacion', 'u')
            ->addSelect('u')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }
}
