<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

class NumberFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public const CRITERIA_BIGGER_THAN = 'bigger_than';

    public const CRITERIA_SMALLER_THAN = 'smaller_than';

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.equal',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.not_equal',
            static::CRITERIA_BIGGER_THAN => 'araise_table.filter.operator.bigger_than',
            static::CRITERIA_SMALLER_THAN => 'araise_table.filter.operator.smaller_than',
        ];
    }

    public function getValueField(?string $value = null, ?string $operator = null): string
    {
        if (! is_numeric($value)) {
            $value = '0';
        }

        return sprintf('<input type="number" step="any" name="{name}" value="%s" class="form-control">', $value);
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $queryBuilder->setParameter($parameterName, static::prepareQueryValue($value));
        $column = $this->getOption(static::OPT_COLUMN);
        return match ($operator) {
            static::CRITERIA_EQUAL => $queryBuilder->expr()->eq($column, ':'.$parameterName),
            static::CRITERIA_NOT_EQUAL => $queryBuilder->expr()->neq($column, ':'.$parameterName),
            static::CRITERIA_BIGGER_THAN => $queryBuilder->expr()->gt($column, ':'.$parameterName),
            static::CRITERIA_SMALLER_THAN => $queryBuilder->expr()->lt($column, ':'.$parameterName),
            default => false,
        };
    }

    protected static function prepareQueryValue($value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
