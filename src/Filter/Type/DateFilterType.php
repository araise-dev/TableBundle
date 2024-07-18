<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

class DateFilterType extends DatetimeFilterType
{
    public function getValueField(?string $value = null, ?string $operator = null): string
    {
        $date = \DateTime::createFromFormat(static::getQueryDataFormat(), (string) $value) ?: new \DateTime();
        $value = $date->format(static::getDateFormat());

        return sprintf(
            '<input type="date" name="{name}" value="%s" data-controller="araise--core-bundle--datetime" data-araise--core-bundle--datetime-lang-value="%s">',
            $operator !== static::CRITERIA_IS_EMPTY ? $value : '',
            'de'
        );
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        if ($operator === static::CRITERIA_EQUAL) {
            $date = $this->prepareDateValue($value);
            $startOfDate = (clone $date)->setTime(0, 0);
            $endOfDate = (clone $date)->setTime(23, 59, 59);
            $queryBuilder->setParameter($parameterName.'_start', $startOfDate);
            $queryBuilder->setParameter($parameterName.'_end', $endOfDate);
            return $queryBuilder->expr()->andX(
                $queryBuilder->expr()->gte(
                    $this->getOption(static::OPT_COLUMN),
                    sprintf(':%s', $parameterName.'_start')
                ),
                $queryBuilder->expr()->lte(
                    $this->getOption(static::OPT_COLUMN),
                    sprintf(':%s', $parameterName.'_end')
                )
            );
        }
        return parent::toDql($operator, $value, $parameterName, $queryBuilder);
    }

    protected function prepareDateValue(string $value): \DateTime
    {
        return \DateTime::createFromFormat(static::getDateFormat(), $value) ?: new \DateTime();
    }

    protected static function getDateFormat(): string
    {
        return 'Y-m-d';
    }

    protected static function getQueryDataFormat(): string
    {
        return 'Y-m-d';
    }
}
