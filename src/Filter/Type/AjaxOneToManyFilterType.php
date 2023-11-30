<?php

declare(strict_types=1);
/*
 * Copyright (c) 2017, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace araise\TableBundle\Filter\Type;

use araise\CoreBundle\Util\StringConverter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AjaxOneToManyFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public const OPT_TARGET_CLASS = 'target_class';

    public const OPT_JSON_SEARCH_URL = 'json_search_url';

    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.contains',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.contains_not',
        ];
    }

    public function getValueField(?string $value = '0'): string
    {
        $targetClass = $this->getOption(static::OPT_TARGET_CLASS);
        $field = sprintf(
            '<select name="{name}" class="form-control" data-ajax-select data-ajax-entity="%s">',
            $targetClass
        );

        $currentSelection = (int) $value > 0 ? $this->entityManager->getRepository($targetClass)->find((int) $value) : null;
        if ($currentSelection) {
            $field .= sprintf('<option value="%s">%s</option>', $value, StringConverter::toString($currentSelection));
        }

        $field .= '</select>';

        return $field;
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $targetClass = $this->getOption(static::OPT_TARGET_CLASS);
        $targetParameter = 'target_'.hash('crc32', random_bytes(10));
        $queryBuilder->setParameter(
            $targetParameter,
            $this->entityManager->getRepository($targetClass)->find($value)
        );

        $column = $this->getOption(static::OPT_COLUMN);
        return match ($operator) {
            static::CRITERIA_EQUAL => $queryBuilder->expr()->in($column, ':'.$targetParameter),
            static::CRITERIA_NOT_EQUAL => $queryBuilder->expr()->notIn($column, ':'.$targetParameter),
            default => null,
        };
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
