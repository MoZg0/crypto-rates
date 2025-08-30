<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder;

readonly class Select
{
    /**
     * @param string[] $columns
     */
    public function __construct(
        /**
         * @var string[]
         */
        public array $columns,
    ) {
    }

    public function apply(QueryBuilder|DBALQueryBuilder $queryBuilder): QueryBuilder|DBALQueryBuilder
    {
        return $queryBuilder
            ->select($this->columns);
    }
}
