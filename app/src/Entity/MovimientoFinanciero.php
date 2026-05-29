<?php

namespace App\Entity;

use App\Repository\MovimientoFinancieroRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovimientoFinancieroRepository::class)]
#[ORM\Table(name: 'movimientos_financieros')]
class MovimientoFinanciero
{
    public const TIPO_INGRESO = 'INGRESO';
    public const TIPO_EGRESO = 'EGRESO';

    public const CONCEPTO_PAGO_FACTURA = 'PAGO_FACTURA';
    public const CONCEPTO_NOMINA = 'NOMINA';
    public const CONCEPTO_PAGO_PROVEEDOR = 'PAGO_PROVEEDOR';
    public const CONCEPTO_SUMINISTROS = 'SUMINISTROS';
    public const CONCEPTO_OTROS = 'OTROS';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private string $tipo;

    #[ORM\Column(length: 30)]
    private string $concepto;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $importe;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroFactura = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroOperacionBancaria = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }
    public function setTipo(string $tipo): static
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getConcepto(): string
    {
        return $this->concepto;
    }
    public function setConcepto(string $concepto): static
    {
        $this->concepto = $concepto;
        return $this;
    }

    public function getImporte(): string
    {
        return $this->importe;
    }
    public function setImporte(string $importe): static
    {
        $this->importe = $importe;
        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }
    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function getNumeroFactura(): ?string
    {
        return $this->numeroFactura;
    }
    public function setNumeroFactura(?string $v): static
    {
        $this->numeroFactura = $v;
        return $this;
    }

    public function getNumeroOperacionBancaria(): ?string
    {
        return $this->numeroOperacionBancaria;
    }
    public function setNumeroOperacionBancaria(?string $v): static
    {
        $this->numeroOperacionBancaria = $v;
        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}