<?php

declare(strict_types=1);

namespace Rates\Crypto\Domain\Services;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Rates\Crypto\Infrastructure\Binance\ApiAdapter;
use Rates\Crypto\Infrastructure\Persistence\Mappers\RateMapper;
use Rates\Crypto\Infrastructure\Persistence\Repositories\RateRepository;

class RateService
{
    private const int DEFAULT_CHUCK_SIZE = 100;

    public function __construct(
        private readonly ApiAdapter $apiAdapter,
        private readonly RateRepository $rateRepository,
        private readonly RateMapper $rateMapper,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param list<string> $pairs
     */
    public function fetchRates(array $pairs, int $chunkSize = self::DEFAULT_CHUCK_SIZE): void
    {
        if ($chunkSize < 1) {
            $chunkSize = self::DEFAULT_CHUCK_SIZE;
        }

        /** @var list<list<string>> $chunks */
        $chunks = array_chunk($pairs, $chunkSize);
        foreach ($chunks as $i => $chunk) {
            $tickerPrices = $this->apiAdapter->fetchRates($chunk);
            $createdAt = new DateTimeImmutable();

            $rates = $this->rateMapper->mapMany($tickerPrices, $createdAt);

            $this->rateRepository->save($rates);
            $this->rateRepository->clear();

            $this->logger->info(sprintf('Processed chunk #%d (%d rows)', $i + 1, count($chunk)));
        }
    }
}
