<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use araise\CoreBundle\Util\StringConverter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class AjaxRelationFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public const OPT_TARGET_CLASS = 'target_class';

    public const OPT_JSON_SEARCH_URL = 'json_search_url';

    protected static PropertyAccessor $propertyAccessor;

    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.is',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.is_not',
        ];
    }

    public function getValueField(?string $value = '0'): string
    {
        $jsonSearchUrl = $this->getOption(static::OPT_JSON_SEARCH_URL);
        $field = sprintf(
            '<select name="{name}" class="form-control" data-araise--core-bundle--select-url-value="%s" data-controller="araise--core-bundle--select" data-araise--core-bundle--select-required-value="0">',
            $jsonSearchUrl
        );
        $targetClass = $this->getOption(static::OPT_TARGET_CLASS);
        $currentSelection = (int) $value > 0 ? $this->entityManager->getRepository($targetClass)->find((int) $value) : null;
        if ($currentSelection) {
            $field .= sprintf('<option value="%s">%s</option>', $value, StringConverter::toString($currentSelection));
        }

        $field .= '</select>';

        return $field;
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $column = $this->getOption(static::OPT_COLUMN);
        switch ($operator) {
            case static::CRITERIA_EQUAL:
                if ((int) $value) {
                    return $queryBuilder->expr()->eq($column.'.id', (int) $value);
                }

                return $queryBuilder->expr()->isNull($column.'.id');

            case static::CRITERIA_NOT_EQUAL:
                if ((int) $value) {
                    return $queryBuilder->expr()->neq($column.'.id', (int) $value);
                }

                return $queryBuilder->expr()->isNotNull($column.'.id');
        }

        return null;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            static::OPT_TARGET_CLASS => null,
            static::OPT_JSON_SEARCH_URL => null,
        ]);
        $resolver->setAllowedTypes(static::OPT_TARGET_CLASS, ['string']);
        $resolver->setAllowedTypes(static::OPT_JSON_SEARCH_URL, ['string']);
    }
}
