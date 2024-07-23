<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

class DatetimeFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public const CRITERIA_BEFORE = 'before';

    public const CRITERIA_AFTER = 'after';

    public const CRITERIA_IN_YEAR = 'in_year';

    public const CRITERIA_IS_EMPTY = 'is_empty';

    public function __construct(
        ?string $column = null,
        array $joins = [],
        protected ?RequestStack $requestStack = null
    ) {
        parent::__construct($column, $joins);
    }

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.equal',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.not_equal',
            static::CRITERIA_BEFORE => 'araise_table.filter.operator.before',
            static::CRITERIA_AFTER => 'araise_table.filter.operator.after',
            static::CRITERIA_IN_YEAR => 'araise_table.filter.operator.same_year',
            static::CRITERIA_IS_EMPTY => new FilterOperatorDto('araise_table.filter.operator.is_empty', [
                FilterOperatorDto::OPT_HAS_FILTER_VALUE => false,
            ]),
        ];
    }

    public function getValueField(?string $value = null, ?string $operator = null): string
    {
        $date = \DateTime::createFromFormat(static::getQueryDataFormat(), (string) $value) ?: new \DateTime();
        $value = $date->format(static::getDateFormat());
        $locale = $this->requestStack->getMainRequest()?->getLocale();

        return sprintf(
            '<input type="datetime-local" name="{name}" value="%s" data-controller="araise--core-bundle--datetime" data-araise--core-bundle--datetime-lang-value="%s">',
            $operator !== static::CRITERIA_IS_EMPTY ? $value : '',
            $locale ?? 'en'
        );
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $date = $this->prepareDateValue($value);
        $dateAsString = $date->format(static::getQueryDataFormat());
        $column = $this->getOption(static::OPT_COLUMN);

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->eq(
                    sprintf(':%s', $parameterName),
                    sprintf('%s', $column)
                );
            case static::CRITERIA_NOT_EQUAL:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->neq(
                    sprintf(':%s', $parameterName),
                    sprintf('%s', $column)
                );
            case static::CRITERIA_BEFORE:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->lt($column, sprintf(':%s', $parameterName));
            case static::CRITERIA_AFTER:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->gt($column, sprintf(':%s', $parameterName));
            case static::CRITERIA_IN_YEAR:
                $startYear = clone $date;
                $endYear = clone $date;
                $startYear->modify('first day of January '.date('Y'))->setTime(0, 0, 0);
                $endYear->modify('last day of December '.date('Y'))->setTime(23, 59, 59);
                $queryBuilder->setParameter($parameterName.'_start', $startYear->format(static::getQueryDataFormat()));
                $queryBuilder->setParameter($parameterName.'_end', $endYear->format(static::getQueryDataFormat()));

                return $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gte($column, sprintf(':%s', $parameterName.'_start')),
                    $queryBuilder->expr()->lte($column, sprintf(':%s', $parameterName.'_end'))
                );
            case static::CRITERIA_IS_EMPTY:
                return $queryBuilder->expr()->isNull($column);
        }

        return null;
    }

    protected function prepareDateValue(string $value): \DateTime
    {
        return \DateTime::createFromFormat(static::getDateFormat(), $value) ?: new \DateTime();
    }

    protected static function getDateFormat(): string
    {
        return 'Y-m-d\TH:i';
    }

    protected static function getQueryDataFormat(): string
    {
        return 'Y-m-d\TH:i';
    }
}
