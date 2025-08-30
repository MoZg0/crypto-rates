<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories\Paginators;

class PagePaginator extends OffsetPaginator
{
    public function __construct(
        private readonly int $page,
        int $limit,
    ) {
        $offset = abs($this->page * $limit) - $limit;

        parent::__construct($offset, $limit);
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
