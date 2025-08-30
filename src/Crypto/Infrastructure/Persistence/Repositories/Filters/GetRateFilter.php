<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Persistence\Repositories\Filters;

use DateTimeImmutable;
use Decimal\Decimal;
use Doctrine\ORM\QueryBuilder;

class GetRateFilter
{
    private ?string $id = null {
        get {
            return $this->id;
        }
        set {
            $this->id = $value;
        }
    }

    public ?string $pair = null {
        get {
            return $this->pair;
        }
        set {
            $this->pair = $value;
        }
    }

    private ?Decimal $priceFrom = null {
        get {
            return $this->priceFrom;
        }
        set {
            $this->priceFrom = $value;
        }
    }

    private ?Decimal $priceTo = null {
        get {
            return $this->priceTo;
        }
        set {
            $this->priceTo = $value;
        }
    }

    public ?DateTimeImmutable $createdFrom = null {
        get {
            return $this->createdFrom;
        }
        set {
            $this->createdFrom = $value;
        }
    }

    public ?DateTimeImmutable $createdTo = null {
        get {
            return $this->createdTo;
        }
        set {
            $this->createdTo = $value;
        }
    }

    public function apply(QueryBuilder $queryBuilder): QueryBuilder
    {
        if ($this->id !== null) {
            $queryBuilder->andWhere('rate.id = :id')
                ->setParameter('id', $this->id);
        }

        if ($this->pair !== null) {
            $queryBuilder->andWhere('rate.pair = :pair')
                ->setParameter('pair', $this->pair);
        }

        if ($this->priceFrom !== null) {
            $queryBuilder->andWhere('rate.price >= :priceFrom')
                ->setParameter('priceFrom', $this->priceFrom);
        }

        if ($this->priceTo !== null) {
            $queryBuilder->andWhere('rate.price <= :priceTo')
                ->setParameter('priceTo', $this->priceTo);
        }

        if ($this->createdFrom !== null) {
            $queryBuilder->andWhere('rate.createdAt >= :createdFrom')
                ->setParameter('createdFrom', $this->createdFrom);
        }

        if ($this->createdTo !== null) {
            $queryBuilder->andWhere('rate.createdAt <= :createdTo')
                ->setParameter('createdTo', $this->createdTo);
        }

        return $queryBuilder;
    }
}
