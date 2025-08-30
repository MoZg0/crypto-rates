<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Binance\Models;

use Decimal\Decimal;

class TickerPrice
{
    public function __construct(
        private readonly string $symbol,
        private readonly Decimal $price,
    ) {
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getPrice(): Decimal
    {
        return $this->price;
    }
}
