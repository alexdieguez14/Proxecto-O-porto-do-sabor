<?php

namespace App\Repository;

use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Usuario>
 */
class UsuarioRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    /**
     * Recifra la contraseña del usuario de forma automática con el tiempo.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Usuario) {
            throw new UnsupportedUserException(sprintf('Las instancias de "%s" no están soportadas.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Usuarios con un rol específico.
     */
    public function buscarPorRol(string $role): array
    {
        return array_values(array_filter(
            $this->findBy([], ['apellidos' => 'ASC', 'nombre' => 'ASC']),
            fn(Usuario $u) => in_array($role, $u->getRoles(), true)
        ));
    }

    /**
     * Usuarios del personal aquellos con al menos un rol interno.
     *   
     */
    public function buscarEmpleados(): array
    {
        $staffRoles = ['ROLE_ADMIN', 'ROLE_LOGISTICA', 'ROLE_CONTABILIDAD'];

        return array_values(array_filter(
            $this->findBy([], ['apellidos' => 'ASC', 'nombre' => 'ASC']),
            fn(Usuario $u) => array_intersect($staffRoles, $u->getRoles()) !== []
        ));
    }

    /** Clientes con búsqueda opcional en nombre, apellidos y email. */
    public function buscarClientesFiltrados(?string $busqueda): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_CLIENTE"%')
            ->orderBy('u.apellidos', 'ASC')
            ->addOrderBy('u.nombre', 'ASC');

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('u.nombre LIKE :q OR u.apellidos LIKE :q OR u.email LIKE :q')
               ->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Empleados y filtro de rol opcionales.
     * Cuando $rol es null devuelve todos los roles internos.
     */
    public function buscarEmpleadosFiltrados(?string $busqueda, ?string $rol): array
    {
        $staffRoles = ['ROLE_ADMIN', 'ROLE_LOGISTICA', 'ROLE_CONTABILIDAD'];

        if ($rol !== null && in_array($rol, $staffRoles, true)) {
            $qb = $this->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%"' . $rol . '"%');
        } else {
            $qb = $this->createQueryBuilder('u')
                ->where('u.roles LIKE :r1 OR u.roles LIKE :r2 OR u.roles LIKE :r3')
                ->setParameter('r1', '%"ROLE_ADMIN"%')
                ->setParameter('r2', '%"ROLE_LOGISTICA"%')
                ->setParameter('r3', '%"ROLE_CONTABILIDAD"%');
        }

        $qb->orderBy('u.apellidos', 'ASC')->addOrderBy('u.nombre', 'ASC');

        if ($busqueda !== null && $busqueda !== '') {
            $qb->andWhere('u.nombre LIKE :q OR u.apellidos LIKE :q OR u.email LIKE :q')
               ->setParameter('q', '%' . $busqueda . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
