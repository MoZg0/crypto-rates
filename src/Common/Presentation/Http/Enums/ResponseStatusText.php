<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Enums;

enum ResponseStatusText: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
}
