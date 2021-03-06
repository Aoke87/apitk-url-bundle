<?php

declare(strict_types=1);

namespace Shopping\ApiTKUrlBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Shopping\ApiTKCommonBundle\Exception\MissingDependencyException;
use Shopping\ApiTKUrlBundle\Exception\PaginationException;
use Shopping\ApiTKUrlBundle\Service\ApiService;

/**
 * Class ApiToolkitServiceRepository.
 *
 * @package Shopping\ApiTKUrlBundle\Repository
 */
class ApiToolkitServiceRepository extends ServiceEntityRepository
{
    /**
     * @param ApiService $apiService
     *
     * @throws NonUniqueResultException
     * @throws PaginationException
     *
     * @return array
     */
    public function findByRequest(ApiService $apiService): array
    {
        if (!class_exists(QueryBuilder::class)) {
            throw new MissingDependencyException(
                'You need to install doctrine/orm and doctrine/doctrine-bundle > 2.0 to use ORM-capabilities within ApiTK bundles.'
            );
        }

        $queryBuilder = $this->createQueryBuilder('a');

        $apiService->applyToQueryBuilder($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }
}
