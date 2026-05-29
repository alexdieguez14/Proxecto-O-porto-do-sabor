<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Articulo;

final class StockBajoEvent
{
    public function __construct(
        public readonly Articulo $articulo,
        public readonly int $stockRestante,
    ) {}
}
