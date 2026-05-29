<?php

namespace App\Entity;

use App\Repository\MetodoPagoGuardadoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetodoPagoGuardadoRepository::class)]
#[ORM\Table(name: 'metodos_pago_guardado')]
class MetodoPagoGuardado
{
    public const TIPO_TARJETA = 'TARJETA';
    public const TIPO_CUENTA_BANCARIA = 'CUENTA_BANCARIA';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class, inversedBy: 'metodosPago')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(length: 20)]
    private string $tipo = self::TIPO_TARJETA;

    #[ORM\Column(length: 100)]
    private string $alias = '';

    #[ORM\Column(length: 50)]
    private string $detalleMasked = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tokenMeta = null;

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

    public function getTipo(): string
    {
        return $this->tipo;
    }
    public function setTipo(string $v): static
    {
        $this->tipo = $v;
        return $this;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }
    public function setAlias(string $v): static
    {
        $this->alias = $v;
        return $this;
    }

    public function getDetalleMasked(): string
    {
        return $this->detalleMasked;
    }
    public function setDetalleMasked(string $v): static
    {
        $this->detalleMasked = $v;
        return $this;
    }

    public function getTokenMeta(): ?string
    {
        return $this->tokenMeta;
    }
    public function setTokenMeta(?string $v): static
    {
        $this->tokenMeta = $v;
        return $this;
    }
}
