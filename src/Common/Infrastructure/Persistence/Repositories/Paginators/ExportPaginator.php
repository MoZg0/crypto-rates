<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories\Paginators;

use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder;

class ExportPaginator implements PaginatorInterface
{
    public function __construct(
        private readonly int $limit,
    ) {
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function apply(DBALQueryBuilder|QueryBuilder $queryBuilder): QueryBuilder|DBALQueryBuilder
    {
        $queryBuilder->setMaxResults($this->getLimit());

        return $queryBuilder;
    }

    /**
     * @return array{limit:int}
     */
    public function jsonSerialize(): array
    {
        return [
            'limit' => $this->getLimit(),
        ];
    }
}
