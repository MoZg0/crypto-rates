<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories\Paginators;

use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use JsonSerializable;

interface PaginatorInterface extends JsonSerializable
{
    public function apply(QueryBuilder|DBALQueryBuilder $queryBuilder): QueryBuilder|DBALQueryBuilder;
}
