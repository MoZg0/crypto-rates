<?php

declare(strict_types=1);

namespace Rates\Tests\Unit\Common\Infrastructure\Doctrine\Types;

use Decimal\Decimal;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rates\Common\Infrastructure\Doctrine\Types\DecimalType;

class DecimalTypeTest extends TestCase
{
    private DecimalType $type;
    private MySQLPlatform $platform;

    protected function setUp(): void
    {
        $this->type = new DecimalType();
        $this->platform = new MySQLPlatform();
    }

    public function testConvertToPHPValue(): void
    {
        $result = $this->type->convertToPHPValue('123.45', $this->platform);

        $this->assertInstanceOf(Decimal::class, $result);
        $this->assertEquals('123.45', $result->toString());
    }

    public function testConvertToPHPValueWithNull(): void
    {
        $result = $this->type->convertToPHPValue(null, $this->platform);

        $this->assertNull($result);
    }

    public function testConvertToDatabaseValue(): void
    {
        $decimal = new Decimal('123.45');
        $result = $this->type->convertToDatabaseValue($decimal, $this->platform);

        $this->assertEquals('123.45', $result);
    }

    public function testConvertToDatabaseValueWithNull(): void
    {
        $result = $this->type->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($result);
    }

    public function testConvertToDatabaseValueWithInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->type->convertToDatabaseValue('invalid', $this->platform);
    }

    public function testGetName(): void
    {
        $this->assertEquals(DecimalType::NAME, $this->type->getName());
    }

    public function testSQLDeclaration(): void
    {
        $column = ['precision' => 18, 'scale' => 8];
        $result = $this->type->getSQLDeclaration($column, $this->platform);

        $this->assertStringContainsString('NUMERIC', $result);
    }
}
