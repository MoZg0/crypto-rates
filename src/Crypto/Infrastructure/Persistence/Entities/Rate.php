<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Persistence\Entities;

use DateTimeImmutable;
use Decimal\Decimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidType;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
#[ORM\Table(name: 'rates')]
#[ORM\Index(name: 'idx_pair_created_at', columns: ['pair', 'created_at'])]
class Rate
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, length: 36)]
    private UuidInterface $id;

    #[ORM\Column(type: Types::STRING)]
    private string $pair;

    #[ORM\Column(type: 'decimal_type', precision: 38, scale: 18)]
    private Decimal $price;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $pair,
        Decimal $price,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = Uuid::uuid7();
        $this->pair = $pair;
        $this->price = $price;
        $this->createdAt = $createdAt;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getPair(): string
    {
        return $this->pair;
    }

    public function getPrice(): Decimal
    {
        return $this->price;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
