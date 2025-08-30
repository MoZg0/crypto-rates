<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder;

readonly class Sort
{
    public function __construct(
        public string $sortBy,
        public SortDirection $sortDirection,
    ) {
    }

    public function apply(
        QueryBuilder|DBALQueryBuilder $queryBuilder,
        ?string $alias = null,
    ): QueryBuilder|DBALQueryBuilder {
        $sortBy = $alias !== null ? $alias . '.' . $this->sortBy : $this->sortBy;

        return $queryBuilder
            ->addOrderBy($sortBy, $this->sortDirection->value);
    }
}
