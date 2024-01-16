<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

class BooleanFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.is',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.is_not',
        ];
    }

    public function getValueField(?string $value = '1', ?string $operator = null): string
    {
        return sprintf(
            '<select name="{name}" class="form-control"><option value="1" %s>ausgewählt</option><option value="0" %s>nicht ausgewählt</option></select>',
            $value === '1' ? 'selected' : '',
            $value === '0' ? 'selected' : ''
        );
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $value = $value === '1' ? 'true' : 'false';
        $column = $this->getOption(static::OPT_COLUMN);
        return match ($operator) {
            static::CRITERIA_EQUAL => $queryBuilder->expr()->eq($column, $value),
            static::CRITERIA_NOT_EQUAL => $queryBuilder->expr()->neq($column, $value),
            default => false,
        };
    }
}
