<?php

declare(strict_types=1);

namespace Rates\Crypto\Presentation\Http\PublicV1\Controllers;

use DateTimeImmutable;
use Rates\Common\Presentation\Http\Responses\DataResponse;
use Rates\Common\Presentation\Http\Responses\ListResponse;
use Rates\Crypto\Infrastructure\Persistence\Repositories\Filters\GetRateFilter;
use Rates\Crypto\Infrastructure\Persistence\Repositories\RateRepository;
use Rates\Crypto\Presentation\Http\PublicV1\Mappers\RateMapper;
use Rates\Crypto\Presentation\Http\PublicV1\Requests\DayRateRequest;
use Rates\Crypto\Presentation\Http\PublicV1\Requests\Last24hRateRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

class RateController extends AbstractController
{
    public function __construct(
        private readonly RateRepository $rateRepository,
        private readonly RateMapper $rateMapper,
    ) {
    }

    public function getLast24h(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        Last24hRateRequest $request,
    ): JsonResponse {
        $normalizedPair = $request->getPair();
        $from = new DateTimeImmutable('-24 hours');

        $filter = new GetRateFilter();
        $filter->pair = $normalizedPair;
        $filter->createdFrom = $from;

        $rates = $this->rateRepository->findMany($filter);

        $dtos = $this->rateMapper->mapMany($rates);

        return $this->json(new DataResponse(new ListResponse($dtos)));
    }

    public function getDay(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        DayRateRequest $request,
    ): JsonResponse {
        $normalizedPair = $request->getPair();
        $date = $request->getDate();

        $startOfDay = $date->setTime(0, 0);
        $endOfDay = $date->setTime(23, 59, 59);

        $filter = new GetRateFilter();
        $filter->pair = $normalizedPair;
        $filter->createdFrom = $startOfDay;
        $filter->createdTo = $endOfDay;

        $rates = $this->rateRepository->findMany($filter);

        $dtos = $this->rateMapper->mapMany($rates);

        return $this->json(new DataResponse(new ListResponse($dtos)));
    }
}
