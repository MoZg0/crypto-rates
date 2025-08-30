<?php

declare(strict_types=1);

namespace Rates\Common\Infrastructure\Persistence\Repositories;

/** @phpstan-type SortDirectionValue 'ASC'|'DESC' */
enum SortDirection: string
{
    case ASC = 'asc';
    case DESC = 'desc';

    /**
     * @return array{'asc', 'desc'}
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
