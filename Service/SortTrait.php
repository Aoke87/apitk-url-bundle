<?php
declare(strict_types=1);

namespace Ofeige\Rfc14Bundle\Service;

use Doctrine\ORM\QueryBuilder;
use Ofeige\Rfc14Bundle\Exception\SortException;
use Ofeige\Rfc14Bundle\Input\SortField;
use Symfony\Component\HttpFoundation\RequestStack;
use Ofeige\Rfc14Bundle\Annotation as Rfc14;

/**
 * Trait SortTrait
 *
 * Sort specific methods for the Rfc14Service.
 *
 * @package Ofeige\Rfc14Bundle\Service
 */
trait SortTrait
{
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var SortField[]
     */
    private $sortFields;

    /**
     * @var Rfc14\Sort[]
     */
    private $sorts = [];

    /**
     * Checks if only allowed sort fields were given in the request. Will be called by the event listener.
     *
     * @param Rfc14\Sort[] $sorts
     * @throws SortException
     */
    public function handleAllowedSorts(array $sorts): void
    {
        $this->sorts = $sorts;

        foreach ($this->getSortedFields() as $sortField) {
            if (!$this->isAllowedSortField($sortField)) {
                throw new SortException(
                    sprintf(
                        'Sort "%s" with direction "%s" is not allowed in this request. Available sorts: %s',
                        $sortField->getName(),
                        $sortField->getDirection(),
                        implode(', ', array_map(function(Rfc14\Sort $sort) {
                            return $sort->name . ' (' . implode(', ', $sort->allowedDirections) . ')';
                        }, $sorts))
                    )
                );
            }
        }
    }

    /**
     * Validates a requested sort field against the annotated allowed sorts.
     *
     * @param SortField $sortField
     * @return bool
     */
    private function isAllowedSortField(SortField $sortField): bool
    {
        foreach ($this->sorts as $sort) {
            if ($sort->name !== $sortField->getName()) {
                continue;
            }

            if (in_array($sortField->getDirection(), $sort->allowedDirections)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the annotated sort by name.
     *
     * @param string $name
     * @return Rfc14\Sort|null
     */
    private function getSortByName(string $name): ?Rfc14\Sort
    {
        foreach ($this->sorts as $sort) {
            if ($sort->name === $name) {
                return $sort;
            }
        }

        return null;
    }

    /**
     * Reads the requested sort fields by the query party of the url.
     */
    private function loadSortsFromQuery(): void
    {
        $this->sortFields = [];

        $requestSorts = $this->requestStack->getMasterRequest()->query->get('sort');
        if (!is_array($requestSorts)) {
            return;
        }

        foreach ($requestSorts as $name => $direction) {
            $sortField = new SortField();
            $sortField->setName($name)
                ->setDirection($direction)
                ->setSort($this->getSortByName($name));

            $this->sortFields[] = $sortField;
        }
    }

    /**
     *  Returns all requested sort fields from the client.
     *
     * @return SortField[]
     */
    public function getSortedFields(): array
    {
        if ($this->sortFields === null) {
            $this->loadSortsFromQuery();
        }

        return $this->sortFields;
    }

    /**
     * Returns true if this sort field was given.
     *
     * @param string $name
     * @return bool
     */
    public function hasSortedField(string $name): bool
    {
        return $this->getSortedField($name) !== null;
    }

    /**
     * Returns the sort field for the given name.
     *
     * @param string $name
     * @return SortField|null
     */
    public function getSortedField(string $name): ?SortField
    {
        foreach ($this->getSortedFields() as $sortField) {
            if ($sortField->getName() === $name) {
                return $sortField;
            }
        }

        return null;
    }

    /**
     * Applies all requested sort fields to the query builder.
     *
     * @param QueryBuilder $queryBuilder
     */
    public function applySortedFieldsToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        foreach ($this->getSortedFields() as $sortField) {
            $sortField->applyToQueryBuilder($queryBuilder);
        }
    }
}