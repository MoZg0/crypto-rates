<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories\Paginators;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder;

class OffsetPaginator implements OffsetPaginatorInterface
{
    public function __construct(
        private readonly int $offset,
        private readonly int $limit,
    ) {
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function apply(DBALQueryBuilder|QueryBuilder $queryBuilder): QueryBuilder|DBALQueryBuilder
    {
        $queryBuilder->setFirstResult($this->getOffset());
        $queryBuilder->setMaxResults($this->getLimit());

        return $queryBuilder;
    }

    /**
     * @return array{limit:int, offset:int}
     */
    public function jsonSerialize(): array
    {
        return [
            'limit' => $this->getLimit(),
            'offset' => $this->getOffset(),
        ];
    }
}
