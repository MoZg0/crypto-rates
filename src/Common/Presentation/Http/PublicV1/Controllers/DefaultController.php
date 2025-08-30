<?php

declare(strict_types=1);

namespace Rates\Common\Presentation\Http\PublicV1\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class DefaultController
{
    public function index(): JsonResponse
    {
        return new JsonResponse('Crypto Rates Service');
    }
}
