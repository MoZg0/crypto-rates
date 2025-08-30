<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Persistence\Repositories;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Rates\Common\Infrastructure\Persistence\Repositories\Sort;
use Rates\Common\Infrastructure\Persistence\Repositories\SortDirection;
use Rates\Crypto\Infrastructure\Persistence\Entities\Rate;
use Rates\Crypto\Infrastructure\Persistence\Repositories\Filters\GetRateFilter;

/**
 * @extends ServiceEntityRepository<Rate>
 */
class RateRepository extends ServiceEntityRepository
{
    private const string ALIAS = 'rate';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rate::class);
    }

    /**
     * @param Rate[] $rates
     */
    public function save(array $rates): void
    {
        foreach ($rates as $rate) {
            $this->getEntityManager()->persist($rate);
        }

        $this->getEntityManager()->flush();
    }

    public function clear(): void
    {
        $this->getEntityManager()->clear();
    }

    /**
     * @return Rate[]
     */
    public function findMany(GetRateFilter $filter): array
    {
        $queryBuilder = $this->createQueryBuilder(self::ALIAS);
        $queryBuilder = $filter->apply($queryBuilder);
        /** @var \Doctrine\ORM\QueryBuilder $queryBuilder */
        $queryBuilder = new Sort('createdAt', SortDirection::ASC)
            ->apply($queryBuilder, self::ALIAS);

        /** @var Rate[] */
        return $queryBuilder->getQuery()->getResult();
    }
}
