<?php

declare(strict_types=1);

namespace araise\TableBundle\EventListener;

use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\Event\DataLoadEvent;
use araise\TableBundle\Extension\FilterExtension;
use araise\TableBundle\Table\Table;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;

class FilterEventListener
{
    /**
     * @var Table
     */
    protected $table;

    public function filterResultSet(DataLoadEvent $event)
    {
        $this->table = $event->getTable();

        if (! $this->table->getDataLoader() instanceof DoctrineDataLoader) {
            // we're only able to filter DoctrineTable
            return;
        }

        if (! $this->table->hasExtension(FilterExtension::class)) {
            return;
        }

        $this->addQueryBuilderFilter();
    }

    /**
     * @return QueryBuilder
     */
    private function queryBuilder()
    {
        return $this->table->getDataLoader()->getOption(DoctrineDataLoader::OPT_QUERY_BUILDER);
    }

    private function addQueryBuilderFilter()
    {
        $addedJoins = $this->queryBuilder()->getAllAliases();

        $orX = $this->queryBuilder()->expr()->orX();

        $filterExtension = $this->table->getFilterExtension();

        $filterData = $filterExtension->getFilterData();

        // First, loop all OR's
        foreach ($filterData as $groupIndex => $columns) {
            // Then, loop all AND's
            $andX = $this->queryBuilder()->expr()->andX();

            $filterIndex = 0;
            foreach ($columns as $data) {
                $column = $data['column'];
                ++$filterIndex;

                if (! isset($filterExtension->getFilters()[$column])) {
                    continue;
                }

                $filter = $filterExtension->getFilters()[$column];

                // we could extend here to automatically join (split field on '.')
                foreach ($filter->getType()->getJoins() as $joinAlias => $join) {
                    if (\in_array($joinAlias, $addedJoins, true)) {
                        continue;
                    }
                    $addedJoins[] = $joinAlias;
                    $method = 'join';
                    $conditionType = null;
                    $condition = null;
                    if (\is_array($join)) {
                        if (count($join) === 4) {
                            $conditionType = $join[2];
                            $condition = $join[3];
                        } elseif (count($join) !== 2) {
                            throw new \InvalidArgumentException(sprintf('Invalid join options supplied for "%s".', $joinAlias));
                        }
                        $method = $join[0];
                        $join = $join[1];
                    }
                    $this->queryBuilder()->{$method}($join, $joinAlias, $conditionType, $condition);
                }

                $w = $filter->getType()->toDql(
                    $data['operator'],
                    $data['value'],
                    implode('_', ['filter', (int) $groupIndex, $filterIndex, $filter->getAcronym()]),
                    $this->queryBuilder()
                );

                if ($w instanceof Base || $w instanceof Comparison || $w instanceof Expr\Func) {
                    $andX->add($w);
                } elseif (is_string($w) && (str_contains($w, 'IS NULL') || str_contains($w, 'IS NOT NULL'))) {
                    $andX->add($w);
                } elseif (! \is_bool($w)) {
                    throw new \UnexpectedValueException(sprintf('Bool or %s expected as filter-result, got %s', Expr::class, \get_class($w)));
                }
            }

            if (\count($andX->getParts()) > 1) {
                $orX->add($andX);
            } elseif (\count($andX->getParts()) === 1) {
                $orX->add($andX->getParts()[0]);
            }
        }

        if (\count($orX->getParts()) > 1) {
            $this->queryBuilder()->andWhere($orX);
        } elseif (\count($orX->getParts()) === 1) {
            $this->queryBuilder()->andWhere($orX->getParts()[0]);
        }
    }
}
