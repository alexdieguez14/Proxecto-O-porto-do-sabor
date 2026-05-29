<?php

namespace App\Entity;

use App\Repository\UbicacionAlmacenRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UbicacionAlmacenRepository::class)]
#[ORM\Table(name: 'ubicaciones_almacen')]
class UbicacionAlmacen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $pasillo = null;

    #[ORM\Column(length: 50)]
    private ?string $estanteria = null;

    #[ORM\Column(length: 50)]
    private ?string $nivel = null;

    #[ORM\OneToMany(targetEntity: Articulo::class, mappedBy: 'ubicacion')]
    private Collection $articulos;

    public function __construct()
    {
        $this->articulos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPasillo(): ?string
    {
        return $this->pasillo;
    }

    public function setPasillo(string $pasillo): static
    {
        $this->pasillo = $pasillo;
        return $this;
    }

    public function getEstanteria(): ?string
    {
        return $this->estanteria;
    }

    public function setEstanteria(string $estanteria): static
    {
        $this->estanteria = $estanteria;
        return $this;
    }

    public function getNivel(): ?string
    {
        return $this->nivel;
    }

    public function setNivel(string $nivel): static
    {
        $this->nivel = $nivel;
        return $this;
    }

    public function getArticulos(): Collection
    {
        return $this->articulos;
    }

    public function __toString(): string
    {
        return sprintf('Pasillo %s · Estantería %s · Nivel %s', $this->pasillo, $this->estanteria, $this->nivel);
    }
}
