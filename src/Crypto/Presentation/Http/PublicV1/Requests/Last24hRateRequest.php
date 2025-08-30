<?php

declare(strict_types=1);

namespace Rates\Crypto\Presentation\Http\PublicV1\Requests;

use Symfony\Component\Validator\Constraints as Assert;

class Last24hRateRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Pair is required.', normalizer: 'trim')]
        #[Assert\Type(type: 'string', message: 'Pair must be a string.')]
        public readonly ?string $pair = null,
    ) {
    }

    public function getPair(): string
    {
        $pair = strtoupper(trim($this->pair ?? ''));

        return str_replace('/', '', $pair);
    }
}
