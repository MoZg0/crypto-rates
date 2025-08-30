<?php

declare(strict_types=1);

namespace Rates\Tests\Helpers;

use Doctrine\ORM\EntityManagerInterface;

trait FixturesTrait
{
    /**
     * @param object[]|object $entities
     */
    protected static function seed(array|object $entities): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        if (!is_array($entities)) {
            $entities = [$entities];
        }

        foreach ($entities as $entity) {
            $entityManager->persist($entity);
        }

        $entityManager->flush();
    }

    protected static function clearDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();
        $schemaManager = $connection->createSchemaManager();

        $tables = array_values(array_diff($schemaManager->listTableNames(), self::getIgnoredTables()));

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($tables as $table) {
            $connection->executeStatement('TRUNCATE TABLE ' . $platform->quoteSingleIdentifier($table));
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        $entityManager->clear();
    }

    protected static function getIgnoredTables(): array
    {
        return ['doctrine_migration_versions'];
    }
}
