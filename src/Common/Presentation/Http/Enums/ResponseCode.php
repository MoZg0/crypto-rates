<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\Enums;

enum ResponseCode: string
{
    case UNHANDLED_ERROR = '1000';

    case SERVER_ERROR = '1001';

    case HTTP_ERROR = '1100';

    case ACCESS_DENIED = '1111';

    case UNAUTHORIZED = '1112';

    case VALIDATION_ERROR = '4000';
}
