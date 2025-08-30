<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Dtos;

final readonly class CountMetaDto implements MetaInterface
{
    public function __construct(
        private int $count,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function jsonSerialize(): array
    {
        return [
            'count' => $this->count,
        ];
    }
}
