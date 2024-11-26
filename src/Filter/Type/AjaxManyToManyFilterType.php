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

use Doctrine\ORM\QueryBuilder;

class AjaxManyToManyFilterType extends AjaxOneToManyFilterType
{
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
            static::CRITERIA_EQUAL => $queryBuilder->expr()->in($column, '(:'.$targetParameter.')'),
            static::CRITERIA_NOT_EQUAL => $queryBuilder->expr()->not($queryBuilder->expr()->in($column, '(:'.$targetParameter.')')),
            default => null,
        };
    }
}
