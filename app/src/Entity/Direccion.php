<?php

namespace App\Entity;

use App\Repository\DireccionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DireccionRepository::class)]
#[ORM\Table(name: 'direcciones')]
class Direccion
{
    public const TIPO_ENVIO = 'ENVIO';
    public const TIPO_FACTURACION = 'FACTURACION';
    public const TIPO_AMBAS = 'AMBAS';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class, inversedBy: 'direcciones')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(length: 200)]
    private string $calle = '';

    #[ORM\Column(length: 100)]
    private string $ciudad = '';

    #[ORM\Column(length: 10)]
    private string $codigoPostal = '';

    #[ORM\Column(length: 100)]
    private string $provincia = '';

    #[ORM\Column(length: 100, options: ['default' => 'España'])]
    private string $pais = 'España';

    #[ORM\Column(length: 20, options: ['default' => self::TIPO_AMBAS])]
    private string $tipo = self::TIPO_AMBAS;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $alias = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): Usuario
    {
        return $this->usuario;
    }
    public function setUsuario(Usuario $v): static
    {
        $this->usuario = $v;
        return $this;
    }

    public function getCalle(): string
    {
        return $this->calle;
    }
    public function setCalle(string $v): static
    {
        $this->calle = $v;
        return $this;
    }

    public function getCiudad(): string
    {
        return $this->ciudad;
    }
    public function setCiudad(string $v): static
    {
        $this->ciudad = $v;
        return $this;
    }

    public function getCodigoPostal(): string
    {
        return $this->codigoPostal;
    }
    public function setCodigoPostal(string $v): static
    {
        $this->codigoPostal = $v;
        return $this;
    }

    public function getProvincia(): string
    {
        return $this->provincia;
    }
    public function setProvincia(string $v): static
    {
        $this->provincia = $v;
        return $this;
    }

    public function getPais(): string
    {
        return $this->pais;
    }
    public function setPais(string $v): static
    {
        $this->pais = $v;
        return $this;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }
    public function setTipo(string $v): static
    {
        $this->tipo = $v;
        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }
    public function setAlias(?string $v): static
    {
        $this->alias = $v;
        return $this;
    }

    /** Devuelve un array plano apto para almacenarse como instantánea JSON en un pedido. */
    public function comoInstantanea(): array
    {
        return [
            'calle' => $this->calle,
            'ciudad' => $this->ciudad,
            'codigoPostal' => $this->codigoPostal,
            'provincia' => $this->provincia,
            'pais' => $this->pais,
            'alias' => $this->alias,
        ];
    }
}
