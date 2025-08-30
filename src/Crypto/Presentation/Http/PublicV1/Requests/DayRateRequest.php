<?php

declare(strict_types=1);

namespace Rates\Crypto\Presentation\Http\PublicV1\Requests;

use DateTimeImmutable;
use DateTimeInterface;
use Rates\Common\Domain\Helpers\DateTimeHelper;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Constraints as Assert;

class DayRateRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Pair is required.', normalizer: 'trim')]
        #[Assert\Type(type: 'string', message: 'Pair must be a string.')]
        public readonly ?string $pair = null,

        #[Assert\NotNull(message: 'Date is required.')]
        #[Assert\Type(DateTimeInterface::class)]
        #[Assert\LessThanOrEqual(value: 'now', message: 'Date cannot be in the future.')]
        #[Context([DateTimeNormalizer::FORMAT_KEY => DateTimeHelper::DATE_FORMAT])]
        public readonly ?DateTimeImmutable $date = null,
    ) {
    }

    public function getPair(): string
    {
        $pair = strtoupper(trim($this->pair ?? ''));

        return str_replace('/', '', $pair);
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date ?? new DateTimeImmutable();
    }
}
