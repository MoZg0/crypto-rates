<?php

declare(strict_types=1);

namespace Rates\Crypto\Presentation\Http\PublicV1\Mappers;

use Rates\Common\Domain\Helpers\DateTimeHelper;
use Rates\Crypto\Infrastructure\Persistence\Entities\Rate;
use Rates\Crypto\Presentation\Http\PublicV1\Dtos\RateDto;

class RateMapper
{
    /**
     * @param Rate[] $rates
     * @return RateDto[]
     */
    public function mapMany(array $rates): array
    {
        $result = [];
        foreach ($rates as $rate) {
            $result[] = $this->map($rate);
        }

        return $result;
    }

    public function map(Rate $rate): RateDto
    {
        return new RateDto(
            $rate->getPair(),
            $rate->getPrice(),
            DateTimeHelper::toDateTimeString($rate->getCreatedAt()),
        );
    }
}
