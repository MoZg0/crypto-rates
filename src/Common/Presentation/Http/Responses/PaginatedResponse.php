<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Responses;

use Rates\Common\Presentation\Http\Dtos\MetaInterface;

class PaginatedResponse
{
    /**
     * @param array<array-key, mixed> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly MetaInterface $meta,
    ) {
    }
}
