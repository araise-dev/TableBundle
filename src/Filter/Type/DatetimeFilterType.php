<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

class DatetimeFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public const CRITERIA_BEFORE = 'before';

    public const CRITERIA_AFTER = 'after';

    public const CRITERIA_IN_YEAR = 'in_year';

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.equal',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.not_equal',
            static::CRITERIA_BEFORE => 'araise_table.filter.operator.before',
            static::CRITERIA_AFTER => 'araise_table.filter.operator.after',
            static::CRITERIA_IN_YEAR => 'araise_table.filter.operator.same_year',
        ];
    }

    public function getValueField(?string $value = null): string
    {
        $date = \DateTime::createFromFormat(static::getQueryDataFormat(), (string) $value) ?: new \DateTime();

        return sprintf(
            '<input type="text" name="{name}" value="%s" class="form-control" data-provide="datetimepicker" data-date-format="dd.mm.yyyy HH:ii">',
            $date->format(static::getDateFormat())
        );
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $date = $this->prepareDateValue($value);
        $dateAsString = $date->format(static::getQueryDataFormat());

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->eq(
                    sprintf(':%s', $parameterName),
                    sprintf('%s', $this->getColumn())
                );
            case static::CRITERIA_NOT_EQUAL:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->neq(
                    sprintf(':%s', $parameterName),
                    sprintf('%s', $this->getColumn())
                );
            case static::CRITERIA_BEFORE:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->lt($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_AFTER:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->gt($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_IN_YEAR:
                $startYear = clone $date;
                $endYear = clone $date;
                $startYear->modify('first day of January '.date('Y'))->setTime(0, 0, 0);
                $endYear->modify('last day of December '.date('Y'))->setTime(23, 59, 59);
                $queryBuilder->setParameter($parameterName.'_start', $startYear->format(static::getQueryDataFormat()));
                $queryBuilder->setParameter($parameterName.'_end', $endYear->format(static::getQueryDataFormat()));

                return $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gte($this->getColumn(), sprintf(':%s', $parameterName.'_start')),
                    $queryBuilder->expr()->lte($this->getColumn(), sprintf(':%s', $parameterName.'_end'))
                );
        }

        return null;
    }

    protected function prepareDateValue(string $value): \DateTime
    {
        return \DateTime::createFromFormat(static::getDateFormat(), $value) ?: new \DateTime();
    }

    protected static function getDateFormat(): string
    {
        return 'd.m.Y H:i';
    }

    protected static function getQueryDataFormat(): string
    {
        return 'Y-m-d H:i:s';
    }
}
