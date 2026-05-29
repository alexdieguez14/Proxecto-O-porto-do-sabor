<?php

namespace App\Repository;

use App\Entity\Pedido;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PedidoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedido::class);
    }

    /** Pedidos visibles en el panel de Logística. */
    public function buscarParaLogistica(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.estado IN (:estados)')
            ->setParameter('estados', [Pedido::ESTADO_PREPARACION, Pedido::ESTADO_LISTO_ENVIO])
            ->orderBy('p.creadoEn', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Suma de los totales de todos los pedidos pagados. */
    public function totalPagados(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.total)')
            ->where('p.estadoContable = :pagado')
            ->setParameter('pagado', Pedido::CONTABLE_PAGADO)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    /** Suma de los totales de pedidos pendientes de pago. */
    public function totalPendientes(): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.total)')
            ->where('p.estadoContable = :pendiente')
            ->setParameter('pendiente', Pedido::CONTABLE_PENDIENTE)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0.0);
    }

    /** Todos los pedidos de un cliente por email, del más reciente al más antiguo. */
    public function buscarPorEmailCliente(string $email): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.clienteEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('p.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Pedidos de un cliente con filtros opcionales de estado y método de pago. */
    public function buscarPorEmailClienteFiltrados(string $email, ?string $estado, ?string $metodoPago): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.clienteEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('p.creadoEn', 'DESC');

        if ($estado !== null) {
            $qb->andWhere('p.estado = :estado')
               ->setParameter('estado', $estado);
        }

        if ($metodoPago !== null) {
            $qb->andWhere('p.metodoPago = :metodoPago')
               ->setParameter('metodoPago', $metodoPago);
        }

        return $qb->getQuery()->getResult();
    }

    /** Pedidos de logística con filtros opcionales de método de pago y email. */
    public function buscarParaLogisticaFiltrados(?string $metodoPago, ?string $busqueda, ?string $estado = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.estado IN (:estados)')
            ->setParameter('estados', [
                Pedido::ESTADO_PREPARACION,
                Pedido::ESTADO_LISTO_ENVIO,
                Pedido::ESTADO_ENVIADO,
            ])
            ->orderBy('p.creadoEn', 'ASC');

        if ($estado !== null) {
            $qb->andWhere('p.estado = :estado')
               ->setParameter('estado', $estado);
        }

        if ($metodoPago !== null) {
            $qb->andWhere('p.metodoPago = :metodoPago')
               ->setParameter('metodoPago', $metodoPago);
        }

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('p.clienteEmail LIKE :q')
               ->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /** Pedidos visibles en el panel de Contabilidad (enviados o pagados). */
    public function buscarParaContabilidad(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.estado IN (:estados)')
            ->setParameter('estados', [Pedido::ESTADO_ENVIADO, Pedido::ESTADO_PAGADO])
            ->orderBy('p.creadoEn', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pedidos con estadoContable PENDIENTE_PAGO cuyo plazo de carencia ha expirado.
     */
    public function buscarPendientesVencidos(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        /** @var list<int> $ids */
        $ids = $conn->fetchFirstColumn(
            'SELECT id FROM pedidos
             WHERE estado_contable = :pendiente
               AND enviado_en IS NOT NULL
               AND DATEDIFF(CURDATE(), enviado_en) > dias_carencia',
            ['pendiente' => Pedido::CONTABLE_PENDIENTE]
        );

        if ($ids === []) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /** Los n pedidos más recientes, de cualquier estado. */
    public function buscarRecientes(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.creadoEn', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** Número de pedidos creados hoy y su suma total. */
    public function contarYTotalizarHoy(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) AS cantidad, COALESCE(SUM(p.total), 0) AS total')
            ->where('p.creadoEn >= :hoy')
            ->setParameter('hoy', new \DateTimeImmutable('today midnight'))
            ->getQuery()
            ->getSingleResult();

        return [
            'cantidad' => (int) $result['cantidad'],
            'total'    => (float) $result['total'],
        ];
    }

    /**
     * Pedidos de contabilidad con filtros opcionales.
     * metodoPago y busqueda se aplican en SQL.
     * estadoContable se aplica en PHP porque es un valor que no esta en la bd
     */
    public function buscarParaContabilidadFiltrados(
        ?string $estadoContable,
        ?string $metodoPago,
        ?string $busqueda
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->where('p.estado IN (:estados)')
            ->setParameter('estados', [Pedido::ESTADO_ENVIADO, Pedido::ESTADO_PAGADO])
            ->orderBy('p.creadoEn', 'DESC');

        if ($metodoPago !== null) {
            $qb->andWhere('p.metodoPago = :metodoPago')
               ->setParameter('metodoPago', $metodoPago);
        }

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('p.clienteEmail LIKE :q')
               ->setParameter('q', '%' . $busqueda . '%');
        }

        $pedidos = $qb->getQuery()->getResult();

        if ($estadoContable !== null) {
            $pedidos = array_values(array_filter(
                $pedidos,
                fn(Pedido $p) => $p->getEstadoContableDisplay() === $estadoContable
            ));
        }

        return $pedidos;
    }
}
