<?php

declare(strict_types=1);

namespace Rates\Tests\Factories\Crypto;

use DateTimeImmutable;
use Decimal\Decimal;
use Faker\Factory;
use Rates\Crypto\Infrastructure\Persistence\Entities\Rate;

class RateFactory
{
    public static function create(
        ?string $pair = null,
        ?string $price = null,
        ?DateTimeImmutable $createdAt = null,
    ): Rate {
        $factory = Factory::create();

        $pair = $pair ?? 'BTCEUR';
        $price = $price ?? (string) $factory->randomFloat(18, 0.0000001, 100000);
        $createdAt = $createdAt ?? DateTimeImmutable::createFromMutable($factory->dateTime());

        return new Rate(
            pair: $pair,
            price: new Decimal($price),
            createdAt: $createdAt,
        );
    }
}
