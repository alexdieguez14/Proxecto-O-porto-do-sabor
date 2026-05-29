<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\StockBajoEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StockBajoSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [StockBajoEvent::class => 'onStockBajo'];
    }

    public function onStockBajo(StockBajoEvent $event): void
    {
        $this->logger->warning(
            'Stock bajo — artículo #{id} "{titulo}": {stock} unidades restantes.',
            [
                'id'     => $event->articulo->getId(),
                'titulo' => $event->articulo->getTitulo(),
                'stock'  => $event->stockRestante,
            ]
        );
    }
}
