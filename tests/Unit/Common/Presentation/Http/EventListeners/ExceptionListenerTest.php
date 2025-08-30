<?php

declare(strict_types=1);

namespace Unit\Common\Presentation\Http\EventListeners;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Rates\Common\Presentation\Http\Enums\ResponseCode;
use Rates\Common\Presentation\Http\EventListeners\ExceptionListener;
use RuntimeException;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;
use Error;

class ExceptionListenerTest extends TestCase
{
    #[DataProvider('exceptionsProvider')]
    public function testOnKernelExceptionHandlesDifferentThrowables(
        callable $factory,
        int $expectedStatus,
        string $expectedMessage,
        string $expectedErrorCode,
    ): void {
        $exception = $factory();

        $listener = new ExceptionListener();

        $event = $this->createExceptionEvent($exception);
        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertInstanceOf(JsonResponse::class, $response, 'Response must be JsonResponse');
        self::assertSame($expectedStatus, $response->getStatusCode());

        $payload = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);
        self::assertSame('error', $payload['status'] ?? null);
        self::assertSame($expectedMessage, $payload['message'] ?? null);
        self::assertSame($expectedErrorCode, $payload['errorCode'] ?? null);
        self::assertArrayHasKey('data', $payload);
    }

    public function testValidationFailedDirect(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Must be positive', null, [], null, 'amount', '-5'),
            new ConstraintViolation('Required', null, [], null, 'name', ''),
        ]);
        $exception = new ValidationFailedException(new stdClass(), $violations);

        $listener = new ExceptionListener();

        $event = $this->createExceptionEvent($exception);
        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $payload = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $errors  = $payload['data']['errors'] ?? null;

        self::assertIsArray($errors);
        self::assertCount(2, $errors);

        self::assertSame('amount', $errors[0]['property']);
        self::assertSame('Must be positive', $errors[0]['message']);
        self::assertSame('-5', $errors[0]['invalidValue']);

        self::assertSame('name', $errors[1]['property']);
        self::assertSame('Required', $errors[1]['message']);
        self::assertSame('', $errors[1]['invalidValue']);
    }

    public function testUnprocessableWithPreviousValidationFailed(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Invalid email', null, [], null, 'email', 'not-an-email'),
        ]);
        $prev = new ValidationFailedException(new stdClass(), $violations);

        $exception = new UnprocessableEntityHttpException('Bad payload', $prev);

        $listener = new ExceptionListener();

        $event = $this->createExceptionEvent($exception);
        $listener->onKernelException($event);

        $response = $event->getResponse();
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());

        $payload = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Validation error.', $payload['message']);
        self::assertSame(ResponseCode::VALIDATION_ERROR->value, $payload['errorCode']);

        $errors = $payload['data']['errors'] ?? null;
        self::assertIsArray($errors);
        self::assertCount(1, $errors);
        self::assertSame('email', $errors[0]['property']);
        self::assertSame('Invalid email', $errors[0]['message']);
        self::assertSame('not-an-email', $errors[0]['invalidValue']);
    }

    public static function exceptionsProvider(): Generator
    {
        yield 'unauthorized' => [
            'factory' => fn(): Throwable => new UnauthorizedHttpException('Bearer', 'Unauthorized!'),
            'expectedStatus' => Response::HTTP_UNAUTHORIZED,
            'expectedMessage' => 'Unauthorized!',
            'expectedErrorCode' => ResponseCode::UNAUTHORIZED->value,
        ];

        yield 'access_denied' => [
            'factory' => fn(): Throwable => new AccessDeniedHttpException(),
            'expectedStatus' => Response::HTTP_FORBIDDEN,
            'expectedMessage' => 'Access denied',
            'expectedErrorCode' => ResponseCode::ACCESS_DENIED->value,
        ];

        yield 'http_exception' => [
            'factory' => fn(): Throwable => new HttpException(418, "I'm a teapot"),
            'expectedStatus' => 418,
            'expectedMessage' => "I'm a teapot",
            'expectedErrorCode' => ResponseCode::HTTP_ERROR->value,
        ];

        yield 'php_error' => [
            'factory' => fn(): Throwable => new Error('Fatal boom'),
            'expectedStatus' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'expectedMessage' => 'Server error.',
            'expectedErrorCode' => ResponseCode::SERVER_ERROR->value,
        ];

        yield 'unhandled_exception' => [
            'factory' => fn(): Throwable => new RuntimeException('Something went wrong'),
            'expectedStatus' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'expectedMessage' => 'Something went wrong',
            'expectedErrorCode' => ResponseCode::UNHANDLED_ERROR->value,
        ];

        yield 'validation_failed_direct' => [
            'factory' => function (): Throwable {
                $violations = new ConstraintViolationList([
                    new ConstraintViolation('x', null, [], null, 'a', 'b'),
                ]);

                return new ValidationFailedException(new stdClass(), $violations);
            },
            'expectedStatus' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'expectedMessage' => 'Validation error.',
            'expectedErrorCode' => ResponseCode::VALIDATION_ERROR->value,
        ];

        yield 'unprocessable_with_previous_validation' => [
            'factory' => function (): Throwable {
                $violations = new ConstraintViolationList([
                    new ConstraintViolation('Bad', null, [], null, 'field', 'z'),
                ]);
                $prev = new ValidationFailedException(new stdClass(), $violations);

                return new UnprocessableEntityHttpException('Payload invalid', $prev);
            },
            'expectedStatus' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'expectedMessage' => 'Validation error.',
            'expectedErrorCode' => ResponseCode::VALIDATION_ERROR->value,
        ];
    }

    private function createExceptionEvent(Throwable $throwable): ExceptionEvent
    {
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };

        $request = Request::create('/test');

        return new ExceptionEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $throwable
        );
    }
}
