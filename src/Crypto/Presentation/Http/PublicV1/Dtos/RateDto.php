<?php

declare(strict_types=1);

namespace Rates\Crypto\Presentation\Http\PublicV1\Dtos;

use Decimal\Decimal;
use JsonSerializable;

class RateDto implements JsonSerializable
{
    private const int SCALE = 18;

    public function __construct(
        private readonly string $pair,
        private readonly Decimal $price,
        private readonly string $createdAt,
    ) {
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function getPrice(): Decimal
    {
        return $this->price;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @return array{pair: string, price: string, createdAt: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'pair' => $this->pair,
            'price' => $this->price->toFixed(self::SCALE),
            'createdAt' => $this->createdAt,
        ];
    }
}
