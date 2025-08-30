<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Persistence\Mappers;

use DateTimeImmutable;
use Rates\Crypto\Infrastructure\Binance\Models\TickerPrice;
use Rates\Crypto\Infrastructure\Persistence\Entities\Rate;

class RateMapper
{
    /**
     * @param TickerPrice[] $tickerPrices
     * @return Rate[]
     */
    public function mapMany(array $tickerPrices, DateTimeImmutable $createdAt): array
    {
        $rates = [];
        foreach ($tickerPrices as $tickerPrice) {
            $rates[] = $this->map($tickerPrice, $createdAt);
        }

        return $rates;
    }

    public function map(TickerPrice $tickerPrice, DateTimeImmutable $createdAt): Rate
    {
        return new Rate(
            $tickerPrice->getSymbol(),
            $tickerPrice->getPrice(),
            $createdAt,
        );
    }
}
