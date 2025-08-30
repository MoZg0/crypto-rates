<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Dtos;

final readonly class MetaDto implements MetaInterface
{
    private int $pageCount;

    public function __construct(
        private int $pageSize,
        private int $pageNumber,
        private int $totalCount,
    ) {
        $this->pageCount = $pageSize === 0 ? 0 : (int) ceil($totalCount / $pageSize);
    }

    /**
     * @return array<string, int>
     */
    public function jsonSerialize(): array
    {
        return [
            'page_size' => $this->pageSize,
            'page_count' => $this->pageCount,
            'page_number' => $this->pageNumber,
            'total_count' => $this->totalCount,
        ];
    }
}
