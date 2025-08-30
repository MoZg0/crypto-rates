<?php

declare(strict_types=1);

namespace Rates\Tests\Functional\Crypto\Http;

use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use Rates\Tests\Factories\Crypto\RateFactory;
use Rates\Tests\Helpers\EntityTrait;
use Rates\Tests\Helpers\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RateControllerTest extends WebTestCase
{
    use EntityTrait;
    use FixturesTrait;

    private const string LAST_24H_ENDPOINT = '/api/rates/last-24h';
    private const string DAY_ENDPOINT = '/api/rates/day';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        self::clearDatabase();
    }

    public static function getResponseConsistencyDataProvider(): Generator
    {
        $now = new DateTimeImmutable();

        yield 'Last 24h consistency' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', $now->modify('-12 hours')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', $now->modify('-6 hours')),
            ],
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR'],
            'requestCount' => 5
        ];

        yield 'Day endpoint consistency' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-01-15 12:00:00')),
            ],
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR', 'date' => '2024-01-15'],
            'requestCount' => 3
        ];
    }

    public static function getHttpHeadersTestsDataProvider(): Generator
    {
        yield 'Valid Accept header - application/json' => [
            'headers' => ['HTTP_ACCEPT' => 'application/json'],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Valid Accept header - wildcard' => [
            'headers' => ['HTTP_ACCEPT' => '*/*'],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Invalid Accept header - text/html' => [
            'headers' => ['HTTP_ACCEPT' => 'text/html'],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Custom User-Agent' => [
            'headers' => ['HTTP_USER_AGENT' => 'TestBot/1.0'],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Malicious User-Agent' => [
            'headers' => ['HTTP_USER_AGENT' => '<script>alert("xss")</script>'],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Very long User-Agent' => [
            'headers' => ['HTTP_USER_AGENT' => str_repeat('A', 10000)],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Invalid Content-Type' => [
            'headers' => ['CONTENT_TYPE' => 'application/xml'],
            'expectedStatusCode' => Response::HTTP_OK
        ];
    }

    public static function getPerformanceTestsDataProvider(): Generator
    {
        $now = new DateTimeImmutable();
        $entities = [];

        for ($i = 0; $i < 1000; $i++) {
            $entities[] = RateFactory::create(
                'BTCEUR',
                (string) (50000 + $i),
                $now->modify("-$i minutes")
            );
        }

        yield 'Large dataset performance - last 24h' => [
            'entities' => $entities,
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR'],
            'expectedMaxCount' => 1000
        ];

        yield 'Large dataset performance - day' => [
            'entities' => array_slice($entities, 0, 100),
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR', 'date' => $now->format('Y-m-d')],
            'expectedMaxCount' => 100
        ];
    }

    public static function getPrecisionTestsDataProvider(): Generator
    {
        $now = new DateTimeImmutable();

        yield 'Maximum decimal precision' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.123456789123456789', $now->modify('-1 hour'))
            ],
            'pair' => 'BTC/EUR',
            'expectedPrice' => '50000.123456789123456789'
        ];

        yield 'Large price values' => [
            'entities' => [
                RateFactory::create('BTCEUR', '999999999.999999999999999999', $now->modify('-1 hour'))
            ],
            'pair' => 'BTC/EUR',
            'expectedPrice' => '999999999.999999999999999999'
        ];

        yield 'Small price values' => [
            'entities' => [
                RateFactory::create('BTCEUR', '0.000000010000000000', $now->modify('-1 hour'))
            ],
            'pair' => 'BTC/EUR',
            'expectedPrice' => '0.000000010000000000'
        ];

        yield 'Trailing zeros precision' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', $now->modify('-1 hour'))
            ],
            'pair' => 'BTC/EUR',
            'expectedPrice' => '50000.000000000000000000'
        ];

        yield 'Scientific notation edge case' => [
            'entities' => [
                RateFactory::create('BTCEUR', '0.000000000000000001', $now->modify('-1 hour'))
            ],
            'pair' => 'BTC/EUR',
            'expectedPrice' => '0.000000000000000001'
        ];
    }

    public static function getBoundaryConditionsDataProvider(): Generator
    {
        $minDate = new DateTimeImmutable('1970-01-01');

        yield 'Minimum date boundary' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', $minDate)
            ],
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR', 'date' => '1970-01-01'],
            'expectedCount' => 1,
            'expectedPrices' => ['50000.000000000000000000']
        ];

        yield 'Daylight saving time transition' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-03-31 01:30:00')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', new DateTimeImmutable('2024-03-31 03:30:00')),
            ],
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR', 'date' => '2024-03-31'],
            'expectedCount' => 2,
            'expectedPrices' => ['50000.000000000000000000', '51000.000000000000000000']
        ];

        yield 'New Year boundary' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2023-12-31 23:59:59')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', new DateTimeImmutable('2024-01-01 00:00:00')),
            ],
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR', 'date' => '2024-01-01'],
            'expectedCount' => 1,
            'expectedPrices' => ['51000.000000000000000000']
        ];

        yield 'Microsecond precision boundary' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-01-01 12:00:00.000000')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', new DateTimeImmutable('2024-01-01 12:00:00.000001')),
            ],
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR', 'date' => '2024-01-01'],
            'expectedCount' => 2,
            'expectedPrices' => ['50000.000000000000000000', '51000.000000000000000000']
        ];
    }

    #[DataProvider('getResponseConsistencyDataProvider')]
    public function testResponseConsistency(
        array $entities,
        string $endpoint,
        array $queryParams,
        int $requestCount = 3
    ): void {
        self::seed($entities);

        $responses = [];
        for ($i = 0; $i < $requestCount; $i++) {
            $this->client->request(Request::METHOD_GET, $endpoint, $queryParams);
            $this->assertSuccessfulResponse();
            $responseData = $this->getResponseData();

            foreach ($responseData['data']['items'] as &$item) {
                $createdAt = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $item['createdAt']);
                $item['createdAt'] = $createdAt->format('Y-m-d\TH:i:s.000000P');
            }

            unset($item);

            $responses[] = $responseData;
        }

        for ($i = 1, $count = count($responses); $i < $count; $i++) {
            $this->assertEquals(
                $responses[0],
                $responses[$i],
                'Response should be consistent across multiple requests',
            );
        }
    }

    public static function getSecurityTestsDataProvider(): Generator
    {
        yield 'SQL injection in pair parameter' => [
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => "BTC'; DROP TABLE rates; --"],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'XSS attempt in pair parameter' => [
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => '<script>alert("xss")</script>'],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Very long pair parameter' => [
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => str_repeat('A', 10000)],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Null byte injection' => [
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => "BTC\x00EUR"],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'Unicode characters in pair' => [
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => '\u0411\u0422\u0426/\u0415\u0423\u0420'],
            'expectedStatusCode' => Response::HTTP_OK
        ];

        yield 'SQL injection in date parameter' => [
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => [
                'pair' => 'BTC/EUR',
                'date' => "2024-01-01'; DROP TABLE rates; --"
            ],
            'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY
        ];

        yield 'Path traversal in date' => [
            'endpoint' => self::DAY_ENDPOINT,
            'queryParams' => [
                'pair' => 'BTC/EUR',
                'date' => '../../../etc/passwd'
            ],
            'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY
        ];

        yield 'Special characters in pair' => [
            'endpoint' => self::LAST_24H_ENDPOINT,
            'queryParams' => ['pair' => 'BTC/EUR;rm -rf /'],
            'expectedStatusCode' => Response::HTTP_OK
        ];
    }

    public static function getLast24hSuccessfulDataProvider(): Generator
    {
        $now = new DateTimeImmutable();

        yield 'Single rate within 24h' => [
            'entities' => [RateFactory::create('BTCEUR', '50000.123456789000000000', $now->modify('-12 hours'))],
            'pair' => 'BTC/EUR',
            'expectedCount' => 1,
            'expectedFirstPrice' => '50000.123456789000000000'
        ];

        yield 'Multiple rates within 24h sorted' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', (new DateTimeImmutable())->modify('-23 hours')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', (new DateTimeImmutable())->modify('-12 hours')),
                RateFactory::create('BTCEUR', '49000.000000000000000000', (new DateTimeImmutable())->modify('-1 hour')),
            ],
            'pair' => 'BTC/EUR',
            'expectedCount' => 3,
            'expectedFirstPrice' => '50000.000000000000000000'
        ];

        yield 'No rates found for pair' => [
            'entities' => [RateFactory::create('ETHEUR', '3000.000000000000000000', (new DateTimeImmutable())->modify('-12 hours'))],
            'pair' => 'BTC/EUR',
            'expectedCount' => 0,
            'expectedFirstPrice' => null
        ];

        yield 'Mixed pairs filtering' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', (new DateTimeImmutable())->modify('-12 hours')),
                RateFactory::create('ETHEUR', '3000.000000000000000000', (new DateTimeImmutable())->modify('-6 hours')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', (new DateTimeImmutable())->modify('-1 hour')),
            ],
            'pair' => 'BTC/EUR',
            'expectedCount' => 2,
            'expectedFirstPrice' => '50000.000000000000000000'
        ];

        yield 'Rates older than 24h excluded' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', (new DateTimeImmutable())->modify('-25 hours')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', (new DateTimeImmutable())->modify('-12 hours')),
            ],
            'pair' => 'BTC/EUR',
            'expectedCount' => 1,
            'expectedFirstPrice' => '51000.000000000000000000'
        ];

        yield 'Exact 24h boundary - within range' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', (new DateTimeImmutable())->modify('-24 hours +1 second')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', (new DateTimeImmutable())->modify('-24 hours -1 second')),
            ],
            'pair' => 'BTC/EUR',
            'expectedCount' => 1,
            'expectedFirstPrice' => '50000.000000000000000000'
        ];

        foreach (['btc/eur', 'BTC/EUR', 'BTCEUR', 'btceur', ' BTC/EUR ', 'BtC/EuR'] as $pairVariation) {
            yield "Pair normalization: {$pairVariation}" => [
                'entities' => [RateFactory::create('BTCEUR', '50000.000000000000000000', (new DateTimeImmutable())->modify('-1 hour'))],
                'pair' => $pairVariation,
                'expectedCount' => 1,
                'expectedFirstPrice' => '50000.000000000000000000'
            ];
        }

        yield 'Empty result structure validation' => [
            'entities' => [],
            'pair' => 'NONEXISTENT/EUR',
            'expectedCount' => 0,
            'expectedFirstPrice' => null
        ];
    }

    public static function getValidationErrorsDataProvider(): Generator
    {
        $futureDate = (new DateTimeImmutable())->modify('+1 day')->format('Y-m-d');

        $errorCases = [
            'Last 24h - Missing pair parameter' => [
                'endpoint' => self::LAST_24H_ENDPOINT,
                'queryParams' => [],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Pair is required'
            ],
            'Last 24h - Empty pair parameter' => [
                'endpoint' => self::LAST_24H_ENDPOINT,
                'queryParams' => ['pair' => ''],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Pair is required'
            ],
            'Last 24h - Whitespace only pair' => [
                'endpoint' => self::LAST_24H_ENDPOINT,
                'queryParams' => ['pair' => '   '],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Pair is required'
            ],
            'Day - Missing pair parameter' => [
                'endpoint' => self::DAY_ENDPOINT,
                'queryParams' => ['date' => '2024-01-15'],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Pair is required'
            ],
            'Day - Missing date parameter' => [
                'endpoint' => self::DAY_ENDPOINT,
                'queryParams' => ['pair' => 'BTC/EUR'],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Date is required'
            ],
            'Day - Invalid date format' => [
                'endpoint' => self::DAY_ENDPOINT,
                'queryParams' => ['pair' => 'BTC/EUR', 'date' => '2026'],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'This value should be of type string.'
            ],
            'Day - Wrong date format' => [
                'endpoint' => self::DAY_ENDPOINT,
                'queryParams' => ['pair' => 'BTC/EUR', 'date' => '15-01-2024'],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'This value should be of type string.'
            ],
            'Day - Future date' => [
                'endpoint' => self::DAY_ENDPOINT,
                'queryParams' => ['pair' => 'BTC/EUR', 'date' => $futureDate],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Date cannot be in the future'
            ],
            'Day - Empty pair' => [
                'endpoint' => self::DAY_ENDPOINT,
                'queryParams' => ['pair' => '', 'date' => '2024-01-15'],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Pair is required'
            ],
            'Day - Empty date' => [
                'endpoint' => self::DAY_ENDPOINT,
                'queryParams' => ['pair' => 'BTC/EUR', 'date' => ''],
                'expectedStatusCode' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'expectedErrorMessage' => 'Date is required'
            ]
        ];

        foreach ($errorCases as $testName => $testData) {
            yield $testName => [
                $testData['endpoint'],
                $testData['queryParams'],
                $testData['expectedStatusCode'],
                $testData['expectedErrorMessage']
            ];
        }
    }

    public static function getDaySuccessfulDataProvider(): Generator
    {
        $targetDate = '2024-01-15';

        yield 'Single rate for specific day' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-01-15 12:00:00'))
            ],
            'pair' => 'BTC/EUR',
            'date' => $targetDate,
            'expectedCount' => 1,
            'expectedPrices' => ['50000.000000000000000000']
        ];

        yield 'Multiple rates same day' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-01-15 09:00:00')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', new DateTimeImmutable('2024-01-15 15:00:00')),
                RateFactory::create('BTCEUR', '49000.000000000000000000', new DateTimeImmutable('2024-01-15 21:00:00')),
            ],
            'pair' => 'BTC/EUR',
            'date' => $targetDate,
            'expectedCount' => 3,
            'expectedPrices' => ['50000.000000000000000000', '51000.000000000000000000', '49000.000000000000000000']
        ];

        yield 'Exact midnight boundaries' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-01-15 00:00:00')),
                RateFactory::create('BTCEUR', '51000.000000000000000000', new DateTimeImmutable('2024-01-15 23:59:59')),
                RateFactory::create('BTCEUR', '49000.000000000000000000', new DateTimeImmutable('2024-01-14 23:59:59')),
                RateFactory::create('BTCEUR', '48000.000000000000000000', new DateTimeImmutable('2024-01-16 00:00:00')),
            ],
            'pair' => 'BTC/EUR',
            'date' => $targetDate,
            'expectedCount' => 2,
            'expectedPrices' => ['50000.000000000000000000', '51000.000000000000000000']
        ];

        yield 'No rates for specified day' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-01-14 12:00:00'))
            ],
            'pair' => 'BTC/EUR',
            'date' => $targetDate,
            'expectedCount' => 0,
            'expectedPrices' => null
        ];

        yield 'Leap year test - February 29th' => [
            'entities' => [
                RateFactory::create('BTCEUR', '50000.000000000000000000', new DateTimeImmutable('2024-02-29 12:00:00'))
            ],
            'pair' => 'BTC/EUR',
            'date' => '2024-02-29',
            'expectedCount' => 1,
            'expectedPrices' => ['50000.000000000000000000']
        ];
    }

    #[DataProvider('getHttpHeadersTestsDataProvider')]
    public function testHttpHeaders(
        array $headers,
        int $expectedStatusCode
    ): void {
        $this->client->request(
            Request::METHOD_GET,
            self::LAST_24H_ENDPOINT,
            ['pair' => 'BTC/EUR'],
            [],
            $headers
        );

        $this->assertEquals($expectedStatusCode, $this->client->getResponse()->getStatusCode());

        if ($expectedStatusCode === Response::HTTP_OK) {
            self::assertResponseHeaderSame('Content-Type', 'application/json');
        }
    }

    #[DataProvider('getPerformanceTestsDataProvider')]
    public function testPerformanceAndLimits(
        array $entities,
        string $endpoint,
        array $queryParams,
        int $expectedMaxCount
    ): void {
        self::seed($entities);

        $startTime = microtime(true);
        $this->client->request(Request::METHOD_GET, $endpoint, $queryParams);
        $endTime = microtime(true);

        $this->assertSuccessfulResponse();

        $responseData = $this->getResponseData();
        $this->assertLessThanOrEqual($expectedMaxCount, count($responseData['data']['items']));

        $executionTime = $endTime - $startTime;
        $this->assertLessThan(5.0, $executionTime, 'API response should be under 5 seconds');
    }

    private function assertSuccessfulResponse(): void
    {
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        self::assertResponseHeaderSame('Content-Type', 'application/json');
    }

    private function getResponseData(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private function assertValidRatesStructure(array $responseData, int $expectedCount): void
    {
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('items', $responseData['data']);
        $this->assertIsArray($responseData['data']['items']);
        $this->assertCount($expectedCount, $responseData['data']['items']);
    }

    private function assertValidRateItem(array $item, string $pair): void
    {
        $this->assertArrayHasKey('pair', $item);
        $this->assertArrayHasKey('price', $item);
        $this->assertArrayHasKey('createdAt', $item);

        $normalizedPair = strtoupper(str_replace('/', '', trim($pair)));
        $this->assertEquals($normalizedPair, $item['pair']);

        $this->assertIsString($item['price']);
        $this->assertMatchesRegularExpression('/^\d+\.\d{18}$/', $item['price']);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}\+\d{2}:\d{2}$/',
            $item['createdAt']
        );
    }

    private function assertDateInRange(string $dateString, string $expectedDate): void
    {
        $itemDate = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $dateString);
        $this->assertEquals($expectedDate, $itemDate->format('Y-m-d'));
    }

    private function assertResponseSortedByDate(array $items): void
    {
        for ($i = 1, $count = count($items); $i < $count; $i++) {
            $prevTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $items[$i - 1]['createdAt']);
            $currentTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.uP', $items[$i]['createdAt']);
            $this->assertLessThanOrEqual($currentTime, $prevTime, 'Items should be sorted by createdAt ASC');
        }
    }

    #[DataProvider('getLast24hSuccessfulDataProvider')]
    public function testGetLast24hSuccessful(
        array $entities,
        string $pair,
        int $expectedCount,
        ?string $expectedFirstPrice = null
    ): void {
        self::seed($entities);

        $this->client->request(Request::METHOD_GET, self::LAST_24H_ENDPOINT, ['pair' => $pair]);

        $this->assertSuccessfulResponse();

        $responseData = $this->getResponseData();
        $this->assertValidRatesStructure($responseData, $expectedCount);

        if ($expectedCount > 0) {
            $this->assertValidRateItem($responseData['data']['items'][0], $pair);

            if ($expectedFirstPrice) {
                $this->assertEquals($expectedFirstPrice, $responseData['data']['items'][0]['price']);
            }

            $this->assertResponseSortedByDate($responseData['data']['items']);
        }
    }

    #[DataProvider('getValidationErrorsDataProvider')]
    public function testValidationErrors(
        string $endpoint,
        array $queryParams,
        int $expectedStatusCode,
        string $expectedErrorMessage
    ): void {
        $this->client->request(Request::METHOD_GET, $endpoint, $queryParams);

        $this->assertEquals($expectedStatusCode, $this->client->getResponse()->getStatusCode());

        $responseData = $this->getResponseData();
        $this->assertStringContainsString($expectedErrorMessage, json_encode($responseData));
    }

    #[DataProvider('getSecurityTestsDataProvider')]
    public function testSecurityVulnerabilities(
        string $endpoint,
        array $queryParams,
        int $expectedStatusCode
    ): void {
        $this->client->request(Request::METHOD_GET, $endpoint, $queryParams);

        $this->assertEquals($expectedStatusCode, $this->client->getResponse()->getStatusCode());

        if ($expectedStatusCode === Response::HTTP_OK) {
            $this->assertSuccessfulResponse();
            $responseData = $this->getResponseData();
            $this->assertArrayHasKey('data', $responseData);
            $this->assertArrayHasKey('items', $responseData['data']);
        }
    }

    #[DataProvider('getPrecisionTestsDataProvider')]
    public function testDataPrecision(
        array $entities,
        string $pair,
        string $expectedPrice
    ): void {
        self::seed($entities);

        $this->client->request(Request::METHOD_GET, self::LAST_24H_ENDPOINT, ['pair' => $pair]);

        $this->assertSuccessfulResponse();

        $responseData = $this->getResponseData();
        $this->assertCount(1, $responseData['data']['items']);
        $this->assertEquals($expectedPrice, $responseData['data']['items'][0]['price']);
    }

    #[DataProvider('getBoundaryConditionsDataProvider')]
    public function testBoundaryConditions(
        array $entities,
        string $endpoint,
        array $queryParams,
        int $expectedCount,
        ?array $expectedPrices = null
    ): void {
        self::seed($entities);

        $this->client->request(Request::METHOD_GET, $endpoint, $queryParams);

        $this->assertSuccessfulResponse();

        $responseData = $this->getResponseData();
        $this->assertValidRatesStructure($responseData, $expectedCount);

        if ($expectedPrices) {
            $actualPrices = array_column($responseData['data']['items'], 'price');
            foreach ($expectedPrices as $expectedPrice) {
                $this->assertContains($expectedPrice, $actualPrices);
            }
        }
    }

    #[DataProvider('getDaySuccessfulDataProvider')]
    public function testGetDaySuccessful(
        array $entities,
        string $pair,
        string $date,
        int $expectedCount,
        ?array $expectedPrices = null
    ): void {
        self::seed($entities);

        $this->client->request(
            Request::METHOD_GET,
            self::DAY_ENDPOINT,
            [
                'pair' => $pair,
                'date' => $date,
            ]
        );

        $this->assertSuccessfulResponse();

        $responseData = $this->getResponseData();
        $this->assertValidRatesStructure($responseData, $expectedCount);

        if ($expectedCount > 0) {
            foreach ($responseData['data']['items'] as $item) {
                $this->assertValidRateItem($item, $pair);
                $this->assertDateInRange($item['createdAt'], $date);
            }

            if ($expectedPrices) {
                $actualPrices = array_column($responseData['data']['items'], 'price');
                sort($expectedPrices);
                sort($actualPrices);
                $this->assertEquals($expectedPrices, $actualPrices);
            }
        }
    }
}
