<?php

namespace App\Entity;

use App\Repository\ArticuloRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticuloRepository::class)]
#[ORM\Table(name: 'articulos')]
class Articulo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $titulo = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $tituloEn = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $tituloGl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcionEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descripcionGl = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $precio = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $precioSinIva = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '21.00'])]
    private ?string $iva = '21.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 3)]
    private ?string $peso = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagen = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $stock = 0;

    #[ORM\ManyToOne(targetEntity: Categoria::class, inversedBy: 'articulos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Categoria $categoria = null;

    #[ORM\ManyToOne(targetEntity: Proveedor::class, inversedBy: 'articulos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Proveedor $proveedor = null;

    #[ORM\ManyToOne(targetEntity: UbicacionAlmacen::class, inversedBy: 'articulos')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?UbicacionAlmacen $ubicacion = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitulo(): ?string
    {
        return $this->titulo;
    }

    public function setTitulo(string $titulo): static
    {
        $this->titulo = $titulo;
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

    public function getTituloEn(): ?string
    {
        return $this->tituloEn;
    }

    public function setTituloEn(?string $tituloEn): static
    {
        $this->tituloEn = $tituloEn !== null && trim($tituloEn) !== '' ? $tituloEn : null;
        return $this;
    }

    public function getTituloGl(): ?string
    {
        return $this->tituloGl;
    }

    public function setTituloGl(?string $tituloGl): static
    {
        $this->tituloGl = $tituloGl !== null && trim($tituloGl) !== '' ? $tituloGl : null;
        return $this;
    }

    public function getDescripcionEn(): ?string
    {
        return $this->descripcionEn;
    }

    public function setDescripcionEn(?string $descripcionEn): static
    {
        $this->descripcionEn = $descripcionEn !== null && trim($descripcionEn) !== '' ? $descripcionEn : null;
        return $this;
    }

    public function getDescripcionGl(): ?string
    {
        return $this->descripcionGl;
    }

    public function setDescripcionGl(?string $descripcionGl): static
    {
        $this->descripcionGl = $descripcionGl !== null && trim($descripcionGl) !== '' ? $descripcionGl : null;
        return $this;
    }

    /** Devuelve el título en el locale solicitado, con fallback a ES. */
    public function getTituloLocalizado(string $locale): ?string
    {
        return match ($locale) {
            'en' => $this->tituloEn ?: $this->titulo,
            'gl' => $this->tituloGl ?: $this->titulo,
            default => $this->titulo,
        };
    }

    /** Devuelve la descripción en el locale solicitado, con fallback a ES. */
    public function getDescripcionLocalizada(string $locale): ?string
    {
        return match ($locale) {
            'en' => $this->descripcionEn ?: $this->descripcion,
            'gl' => $this->descripcionGl ?: $this->descripcion,
            default => $this->descripcion,
        };
    }

    public function getPrecio(): ?string
    {
        return $this->precio;
    }

    public function setPrecio(string $precio): static
    {
        $this->precio = $precio;
        return $this;
    }

    public function getPrecioSinIva(): ?string
    {
        return $this->precioSinIva;
    }

    public function setPrecioSinIva(?string $precioSinIva): static
    {
        $this->precioSinIva = $precioSinIva;
        return $this;
    }

    public function getIva(): ?string
    {
        return $this->iva;
    }

    public function setIva(string $iva): static
    {
        $this->iva = $iva;
        return $this;
    }

    public function getPeso(): ?string
    {
        return $this->peso;
    }

    public function setPeso(string $peso): static
    {
        $this->peso = $peso;
        return $this;
    }

    public function getImagen(): ?string
    {
        return $this->imagen;
    }

    public function setImagen(?string $imagen): static
    {
        $this->imagen = $imagen;
        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;
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

    public function getUbicacion(): ?UbicacionAlmacen
    {
        return $this->ubicacion;
    }

    public function setUbicacion(?UbicacionAlmacen $ubicacion): static
    {
        $this->ubicacion = $ubicacion;
        return $this;
    }

    public function getCategoria(): ?Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(?Categoria $categoria): static
    {
        $this->categoria = $categoria;
        return $this;
    }
}
