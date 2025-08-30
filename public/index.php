<?php

declare(strict_types=1);

use Rates\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel((string) $context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
