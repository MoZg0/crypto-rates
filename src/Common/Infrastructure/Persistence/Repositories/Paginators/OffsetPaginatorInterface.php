<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories\Paginators;

interface OffsetPaginatorInterface extends PaginatorInterface
{
    public function getOffset(): int;

    public function getLimit(): int;
}
