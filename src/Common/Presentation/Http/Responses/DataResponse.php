<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Responses;

class DataResponse extends SuccessResponse
{
    public function __construct(
        object $data,
    ) {
        parent::__construct();
        $this->data = $data;
    }
}
