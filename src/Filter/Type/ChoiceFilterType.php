<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public const OPT_CHOICES = 'choices';

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.is',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.is_not',
        ];
    }

    public function getValueField(?string $value = null, ?string $operator = null): string
    {
        $result = '<select name="{name}" class="form-control"><option value=""></option>';

        foreach ($this->getOption(self::OPT_CHOICES) as $key => $choice) {
            $result .= sprintf(
                '<option value="%s" %s>%s</option>',
                $key,
                $value === $key ? 'selected' : '',
                $choice
            );
        }

        $result .= '</select>';

        return $result;
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $queryBuilder->setParameter($parameterName, $value);
        $column = $this->getOption(static::OPT_COLUMN);
        return match ($operator) {
            static::CRITERIA_EQUAL => $queryBuilder->expr()->eq($column, ':'.$parameterName),
            static::CRITERIA_NOT_EQUAL => $queryBuilder->expr()->neq($column, ':'.$parameterName),
            default => false,
        };
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault(static::OPT_CHOICES, []);
        $resolver->setAllowedTypes(static::OPT_CHOICES, ['array']);
    }
}
