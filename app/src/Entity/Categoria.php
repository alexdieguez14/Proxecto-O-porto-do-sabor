<?php

namespace App\Entity;

use App\Repository\CategoriaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriaRepository::class)]
#[ORM\Table(name: 'categorias')]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nombre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nombreEn = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nombreGl = null;

    #[ORM\OneToMany(targetEntity: Articulo::class, mappedBy: 'categoria')]
    private Collection $articulos;

    public function __construct()
    {
        $this->articulos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getNombreEn(): ?string
    {
        return $this->nombreEn;
    }

    public function setNombreEn(?string $nombreEn): static
    {
        $this->nombreEn = $nombreEn !== null && trim($nombreEn) !== '' ? $nombreEn : null;
        return $this;
    }

    public function getNombreGl(): ?string
    {
        return $this->nombreGl;
    }

    public function setNombreGl(?string $nombreGl): static
    {
        $this->nombreGl = $nombreGl !== null && trim($nombreGl) !== '' ? $nombreGl : null;
        return $this;
    }

    /** Devuelve el nombre en el locale solicitado, pordefecto a ES. */
    public function getNombreLocalizado(string $locale): ?string
    {
        return match ($locale) {
            'en' => $this->nombreEn ?: $this->nombre,
            'gl' => $this->nombreGl ?: $this->nombre,
            default => $this->nombre,
        };
    }

    public function getArticulos(): Collection
    {
        return $this->articulos;
    }

    public function __toString(): string
    {
        return $this->nombre ?? '';
    }
}
