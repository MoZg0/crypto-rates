<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Responses;

class ListResponse
{
    /**
     * @param array<array-key, mixed> $items
     */
    public function __construct(
        public readonly array $items,
    ) {
    }
}
