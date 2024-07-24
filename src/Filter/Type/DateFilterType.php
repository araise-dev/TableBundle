<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class DateFilterType extends DatetimeFilterType
{
    protected $locale;

    public function __construct(
        ?string $column = null,
        array $joins = [],
        protected ?RequestStack $requestStack = null
    ) {
        parent::__construct($column, $joins);
        $this->locale = $requestStack->getMainRequest()?->getLocale() ?? 'en';
    }

    public function getValueField(?string $value = null, ?string $operator = null): string
    {
        $date = \DateTime::createFromFormat(static::getQueryDataFormat(), (string) $value) ?: new \DateTime();
        $value = $date->format(static::getDateFormat());
        $stimulusAttrs = (new StimulusHelper(null))->createStimulusAttributes();
        $stimulusAttrs
            ->addController('araise/core-bundle/datetime', [
                'lang' => $this->locale,
            ])
        ;
        return sprintf(
            '<input type="date" name="{name}" value="%s" %s>',
            $operator !== static::CRITERIA_IS_EMPTY ? $value : '',
            $stimulusAttrs
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
