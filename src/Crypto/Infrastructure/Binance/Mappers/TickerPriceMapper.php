<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Binance\Mappers;

use Decimal\Decimal;
use Rates\Crypto\Infrastructure\Binance\Exceptions\InvalidResponseException;
use Rates\Crypto\Infrastructure\Binance\Models\TickerPrice;

class TickerPriceMapper
{
    /**
     * @param array<array-key, array{symbol?: mixed, price?: mixed}> $data
     * @return TickerPrice[]
     */
    public function mapMany(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            $result[] = $this->map($item);
        }

        return $result;
    }

    /**
     * @param array{symbol?: mixed, price?: mixed} $tickerData
     */
    private function map(array $tickerData): TickerPrice
    {
        if (!array_key_exists('symbol', $tickerData) || !array_key_exists('price', $tickerData)) {
            throw InvalidResponseException::createFromMissingFields('symbol, price');
        }

        $symbol = $tickerData['symbol'];
        $price  = $tickerData['price'];

        if (!is_string($symbol)) {
            throw InvalidResponseException::createFromInvalidJson('Field "symbol" must be string');
        }

        if (!(is_string($price) || is_int($price) || is_float($price))) {
            throw InvalidResponseException::createFromInvalidJson('Field "price" must be numeric/string');
        }

        $priceString = (string) $price;

        return new TickerPrice(
            symbol: $symbol,
            price: new Decimal($priceString),
        );
    }
}
