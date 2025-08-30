<?php

declare(strict_types=1);

namespace Rates\Tests\Helpers;

use Doctrine\Persistence\AbstractManagerRegistry;

trait EntityTrait
{
    /**
     * @return object[]
     */
    protected static function findEntities(string $entityClass, array $findBy = [], array $orderBy = []): array
    {
        return self::getDoctrine()->getRepository($entityClass)->findBy($findBy, $orderBy);
    }

    protected static function findEntity(string $entityClass, array $findBy = [], array $orderBy = []): ?object
    {
        $repository = self::getDoctrine()->getRepository($entityClass);
        $result = $repository->findBy($findBy, $orderBy, 1, 0);

        return count($result) > 0 ? $result[0] : null;
    }

    protected function refreshEntity(array|object $entities): array|object
    {
        $entityManager = self::getDoctrine()->getManager();

        if (is_array($entities)) {
            foreach ($entities as $entity) {
                $entityManager->refresh($entity);
            }
        } else {
            $entityManager->refresh($entities);
        }

        return $entities;
    }

    protected static function getDoctrine(): AbstractManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }
}
