<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

class TextFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public const CRITERIA_STARTS_WITH = 'starts_with';

    public const CRITERIA_ENDS_WITH = 'ends_with';

    public const CRITERIA_CONTAINS = 'contains';

    public const CRITERIA_IS_EMPTY = 'is_empty';

    public const CRITERIA_IS_NOT_EMPTY = 'is_not_empty';

    public function getOperators(): array
    {
        return [
            static::CRITERIA_CONTAINS => 'araise_table.filter.operator.contains',
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.equal',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.not_equal',
            static::CRITERIA_STARTS_WITH => 'araise_table.filter.operator.starts_with',
            static::CRITERIA_ENDS_WITH => 'araise_table.filter.operator.ends_with',
            static::CRITERIA_IS_EMPTY => 'araise_table.filter.operator.is_empty',
            static::CRITERIA_IS_NOT_EMPTY => 'araise_table.filter.operator.is_not_empty',
        ];
    }

    public function getValueField(?string $value = ''): string
    {
        if (! $value) {
            $value = '';
        }

        return sprintf(
            '<input type="text" name="{name}" value="%s" class="form-control">',
            addcslashes($value, '"')
        );
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $column = $this->getOption(static::OPT_COLUMN);
        switch ($operator) {
            case static::CRITERIA_EQUAL:
                $queryBuilder->setParameter($parameterName, $value);

                return $queryBuilder->expr()->eq($column, ':'.$parameterName);

            case static::CRITERIA_NOT_EQUAL:
                $queryBuilder->setParameter($parameterName, $value);

                return $queryBuilder->expr()->neq($column, ':'.$parameterName);

            case static::CRITERIA_STARTS_WITH:
                $queryBuilder->setParameter($parameterName, $value.'%');

                return $queryBuilder->expr()->like($column, ':'.$parameterName);

            case static::CRITERIA_ENDS_WITH:
                $queryBuilder->setParameter($parameterName, '%'.$value);

                return $queryBuilder->expr()->like($column, ':'.$parameterName);

            case static::CRITERIA_CONTAINS:
                $queryBuilder->setParameter($parameterName, '%'.$value.'%');

                return $queryBuilder->expr()->like($column, ':'.$parameterName);

            case static::CRITERIA_IS_EMPTY:
                $queryBuilder->setParameter($parameterName, '');

                return $queryBuilder->expr()->orX()->addMultiple([
                    $queryBuilder->expr()->isNull($column),
                    $queryBuilder->expr()->eq($column, ':'.$parameterName),
                ]);

            case static::CRITERIA_IS_NOT_EMPTY:
                $queryBuilder->setParameter($parameterName, '');

                return $queryBuilder->expr()->gt($column, ':'.$parameterName);
        }

        return false;
    }
}
