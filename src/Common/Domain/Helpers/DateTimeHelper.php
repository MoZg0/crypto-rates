<?php

declare(strict_types=1);

namespace Rates\Common\Domain\Helpers;

use DateTimeInterface;

class DateTimeHelper
{
    public const string DATE_FORMAT = 'Y-m-d';

    private const string DATETIME_FORMAT = 'Y-m-d\TH:i:s.uP';

    public static function toDateTimeString(DateTimeInterface $dateTime): string
    {
        return $dateTime->format(self::DATETIME_FORMAT);
    }
}
