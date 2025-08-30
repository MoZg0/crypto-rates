<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Responses;

use Rates\Common\Presentation\Http\Enums\ResponseStatusText;
use stdClass;

class SuccessResponse
{
    public readonly string $status;
    public object $data;

    public function __construct()
    {
        $this->status = ResponseStatusText::SUCCESS->value;
        $this->data = new stdClass();
    }
}
