<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

class DateFilterType extends DatetimeFilterType
{
    public function getValueField(?string $value = null, ?string $operator = null): string
    {
        $date = \DateTime::createFromFormat(static::getQueryDataFormat(), (string) $value) ?: new \DateTime();
        $value = $date->format(static::getDateFormat());

        return sprintf(
            '<input type="text" name="{name}" value="%s" class="form-control" data-provide="datetimepicker" data-date-format="dd.mm.yyyy" data-min-view="2">',
            $operator !== static::CRITERIA_IS_EMPTY ? $value : ''
        );
    }

    protected function prepareDateValue(string $value): \DateTime
    {
        return \DateTime::createFromFormat(static::getDateFormat(), $value) ?: new \DateTime();
    }

    protected static function getDateFormat(): string
    {
        return 'd.m.Y';
    }

    protected static function getQueryDataFormat(): string
    {
        return 'Y-m-d';
    }
}
