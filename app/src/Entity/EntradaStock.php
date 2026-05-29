<?php

namespace App\Entity;

use App\Repository\EntradaStockRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EntradaStockRepository::class)]
#[ORM\Table(name: 'entradas_stock')]
class EntradaStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Articulo::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Articulo $articulo;

    #[ORM\ManyToOne(targetEntity: Proveedor::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Proveedor $proveedor = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $cantidad;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numAlbaran = null;

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

    public function getArticulo(): Articulo
    {
        return $this->articulo;
    }
    public function setArticulo(Articulo $articulo): static
    {
        $this->articulo = $articulo;
        return $this;
    }

    public function getProveedor(): ?Proveedor
    {
        return $this->proveedor;
    }
    public function setProveedor(?Proveedor $proveedor): static
    {
        $this->proveedor = $proveedor;
        return $this;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }
    public function setCantidad(int $cantidad): static
    {
        $this->cantidad = $cantidad;
        return $this;
    }

    public function getNumAlbaran(): ?string
    {
        return $this->numAlbaran;
    }
    public function setNumAlbaran(?string $numAlbaran): static
    {
        $this->numAlbaran = $numAlbaran;
        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
