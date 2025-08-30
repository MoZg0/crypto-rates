<?php

declare(strict_types=1);

namespace Rates\Crypto\Infrastructure\Binance;

use GuzzleHttp\Psr7\Request;
use JsonException;
use Nyholm\Psr7\Uri;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Rates\Common\Infrastructure\ContentType;
use Rates\Crypto\Infrastructure\Binance\Exceptions\InvalidResponseException;
use Rates\Crypto\Infrastructure\Binance\Mappers\TickerPriceMapper;
use Rates\Crypto\Infrastructure\Binance\Models\TickerPrice;
use Symfony\Component\HttpFoundation\Response;

class ApiAdapter
{
    private const string BASE_URL = 'https://api.binance.com';
    private const string TICKER_PRICE_ENDPOINT = '/api/v3/ticker/price';

    public function __construct(
        private readonly ClientInterface $httpExternal,
        private readonly TickerPriceMapper $responseMapper,
    ) {
    }

    /**
     * @param list<string> $pairs
     * @return TickerPrice[]
     */
    public function fetchRates(array $pairs): array
    {
        $request = $this->createTickerPriceRequest($pairs);

        $response = $this->httpExternal->sendRequest($request);

        self::validateResponse($response);

        $data = $response->getBody()->getContents();

        try {
            $decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidResponseException::createFromInvalidJson($data);
        }

        if (!is_array($decoded)) {
            throw InvalidResponseException::createFromInvalidJson($data);
        }

        if (!array_is_list($decoded)) {
            $decoded = [$decoded];
        }

        $decoded = array_values(array_filter($decoded, 'is_array'));

        return $this->responseMapper->mapMany($decoded);
    }

    /**
     * @param list<string> $pairs
     */
    private function createTickerPriceRequest(array $pairs): Request
    {
        $uri = new Uri(self::BASE_URL . self::TICKER_PRICE_ENDPOINT);

        if ($pairs !== []) {
            $pairsParam = json_encode($pairs, JSON_UNESCAPED_SLASHES);
            $uri = $uri->withQuery("symbols=$pairsParam");
        }

        return new Request(
            method: 'GET',
            uri: $uri,
            headers: [
                'Content-Type' => ContentType::APPLICATION_JSON->value,
                'Accept' => ContentType::APPLICATION_JSON->value,
            ],
        );
    }

    /**
     * @throws InvalidResponseException
     */
    private static function validateResponse(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            $body = $response->getBody()->getContents();

            throw InvalidResponseException::createFromHttpCode($response->getStatusCode(), $body);
        }
    }
}
