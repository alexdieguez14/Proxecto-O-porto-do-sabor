<?php

namespace App\Entity;

use App\Repository\PedidoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PedidoRepository::class)]
#[ORM\Table(name: 'pedidos')]
class Pedido
{
    /* ── Estados del flujo de trabajo ── */
    public const ESTADO_PREPARACION = 'PREPARACION';
    public const ESTADO_LISTO_ENVIO = 'LISTO_ENVIO';
    public const ESTADO_ENVIADO = 'ENVIADO';
    public const ESTADO_PAGADO = 'PAGADO';

    /* ── Subestados contables ── */
    public const CONTABLE_PENDIENTE = 'PENDIENTE_PAGO';
    public const CONTABLE_VENCIDO = 'VENCIDO_SIN_PAGAR';
    public const CONTABLE_PAGADO = 'PAGADO';

    /* ── Métodos de pago ── */
    public const METODO_TARJETA = 'TARJETA';
    public const METODO_TRANSFERENCIA = 'TRANSFERENCIA';
    public const METODO_CONTRARREEMBOLSO = 'CONTRARREEMBOLSO';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $clienteEmail = null;

    #[ORM\Column(type: Types::JSON)]
    private array $items = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(length: 30)]
    private string $estado = self::ESTADO_PREPARACION;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $estadoContable = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $cantidadTotal = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $bultos = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $enviadoEn = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 30])]
    private int $diasCarencia = 30;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $metodoPago = null;

    #[ORM\Column(type: Types::JSON, nullable: true, name: 'shipping_address')]
    private ?array $direccionEnvio = null;

    #[ORM\Column(type: Types::JSON, nullable: true, name: 'billing_address')]
    private ?array $direccionFacturacion = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroSeguimiento = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Usuario $usuario = null;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClienteEmail(): ?string
    {
        return $this->clienteEmail;
    }
    public function setClienteEmail(string $v): static
    {
        $this->clienteEmail = $v;
        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }
    public function setItems(array $v): static
    {
        $this->items = $v;
        return $this;
    }

    public function getTotal(): ?string
    {
        return $this->total;
    }
    public function setTotal(string $v): static
    {
        $this->total = $v;
        return $this;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }
    public function setEstado(string $v): static
    {
        $this->estado = $v;
        return $this;
    }

    public function getEstadoContable(): ?string
    {
        return $this->estadoContable;
    }
    public function setEstadoContable(?string $v): static
    {
        $this->estadoContable = $v;
        return $this;
    }

    public function getCantidadTotal(): int
    {
        return $this->cantidadTotal;
    }
    public function setCantidadTotal(int $v): static
    {
        $this->cantidadTotal = $v;
        return $this;
    }

    public function getBultos(): ?int
    {
        return $this->bultos;
    }
    public function setBultos(?int $v): static
    {
        $this->bultos = $v;
        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getEnviadoEn(): ?\DateTimeImmutable
    {
        return $this->enviadoEn;
    }
    public function setEnviadoEn(?\DateTimeImmutable $v): static
    {
        $this->enviadoEn = $v;
        return $this;
    }

    public function getDiasCarencia(): int
    {
        return $this->diasCarencia;
    }
    public function setDiasCarencia(int $v): static
    {
        $this->diasCarencia = $v;
        return $this;
    }

    public function getMetodoPago(): ?string
    {
        return $this->metodoPago;
    }
    public function setMetodoPago(?string $v): static
    {
        $this->metodoPago = $v;
        return $this;
    }

    public function getDireccionEnvio(): ?array
    {
        return $this->direccionEnvio;
    }
    public function setDireccionEnvio(?array $v): static
    {
        $this->direccionEnvio = $v;
        return $this;
    }

    public function getDireccionFacturacion(): ?array
    {
        return $this->direccionFacturacion;
    }
    public function setDireccionFacturacion(?array $v): static
    {
        $this->direccionFacturacion = $v;
        return $this;
    }

    public function getNumeroSeguimiento(): ?string
    {
        return $this->numeroSeguimiento;
    }
    public function setNumeroSeguimiento(?string $v): static
    {
        $this->numeroSeguimiento = $v;
        return $this;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }
    public function setUsuario(?Usuario $v): static
    {
        $this->usuario = $v;
        return $this;
    }

    /**
     * Calcula el estado contable en función del pago  
     */
    public function getEstadoContableCalculado(): string
    {
        if ($this->estadoContable === self::CONTABLE_PAGADO) {
            return self::CONTABLE_PAGADO;
        }
        if (
            $this->estado === self::ESTADO_ENVIADO && $this->enviadoEn !== null
            && $this->metodoPago !== self::METODO_TARJETA
        ) {
            $dias = (new \DateTimeImmutable())->diff($this->enviadoEn)->days;
            if ($dias > $this->diasCarencia) {
                return self::CONTABLE_VENCIDO;
            }
        }
        return self::CONTABLE_PENDIENTE;
    }
}
