<?php

declare(strict_types=1);
/*
 * Copyright (c) 2022, whatwedo GmbH
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

namespace whatwedo\TableBundle\Filter;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use whatwedo\TableBundle\Filter\Type\AjaxManyToManyFilterType;
use whatwedo\TableBundle\Filter\Type\AjaxOneToManyFilterType;
use whatwedo\TableBundle\Filter\Type\AjaxRelationFilterType;
use whatwedo\TableBundle\Filter\Type\BooleanFilterType;
use whatwedo\TableBundle\Filter\Type\DateFilterType;
use whatwedo\TableBundle\Filter\Type\DatetimeFilterType;
use whatwedo\TableBundle\Filter\Type\FilterTypeInterface;
use whatwedo\TableBundle\Filter\Type\NumberFilterType;
use whatwedo\TableBundle\Filter\Type\TextFilterType;

class FilterGuesser
{
    private const SCALAR_TYPES = [
        'string' => TextFilterType::class,
        'text' => TextFilterType::class,
        'date' => DateFilterType::class,
        'datetime' => DatetimeFilterType::class,
        'integer' => NumberFilterType::class,
        'float' => NumberFilterType::class,
        'decimal' => NumberFilterType::class,
        'boolean' => BooleanFilterType::class,
    ];

    private const RELATION_TYPE = [
        OneToMany::class => AjaxOneToManyFilterType::class,
        ManyToOne::class => AjaxRelationFilterType::class,
        ManyToMany::class => AjaxManyToManyFilterType::class,
    ];

    public function __construct(
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function getFilterType(
        \ReflectionProperty $property,
        string $accessor,
        string $acronym,
        callable $jsonSearchCallable,
        array $joins,
        string $namespace
    ): ?FilterTypeInterface {
        $all = $this->getAnnotationsAndAttributes($property);
        foreach ($all as $holder) {
            $class = $this->getClass($holder);
            $type = $this->getType($holder, $property);
            $targetEntity = $this->getTargetEntity($holder, $property);
            $result = match ($class) {
                Column::class => $this->newColumnFilter($type, $accessor),
                ManyToMany::class => $this->newManyToManyFilter($class, $acronym, $targetEntity, $jsonSearchCallable, $joins),
                OneToMany::class, ManyToOne::class => $this->newRelationFilter($class, $acronym, $targetEntity, $namespace, $jsonSearchCallable, $joins),
                default => null,
            };
            if ($result) {
                return $result;
            }
        }

        return null;
    }

    private function newColumnFilter(string $type, string $accessor): ?FilterTypeInterface
    {
        if (! isset(self::SCALAR_TYPES[$type])) {
            return null;
        }

        return new (self::SCALAR_TYPES[$type])($accessor);
    }

    private function newManyToManyFilter(string $class, string $acronym, string $targetEntity, callable $jsonSearchCallable, array $joins): ?FilterTypeInterface
    {
        if (! isset(self::RELATION_TYPE[$class])) {
            return null;
        }

        return new (self::RELATION_TYPE[$class])(
            $acronym,
            $targetEntity,
            $this->entityManager,
            $jsonSearchCallable($targetEntity),
            $joins
        );
    }

    private function newRelationFilter(string $class, string $acronym, string $targetEntity, string $namespace, callable $jsonSearchCallable, array $joins): ?FilterTypeInterface
    {
        if (! isset(self::RELATION_TYPE[$class])) {
            return null;
        }

        if (mb_strpos($targetEntity, '\\') === false) {
            $targetEntity = $namespace . '\\' . $targetEntity;
        }

        return new (self::RELATION_TYPE[$class])(
            $acronym,
            $targetEntity,
            $this->entityManager,
            $jsonSearchCallable($targetEntity),
            $joins
        );
    }

    private function getAnnotationsAndAttributes(\ReflectionProperty $property): ?array
    {
        $annotations = (new AnnotationReader())->getPropertyAnnotations($property);
        $attributes = $property->getAttributes();

        return [...$annotations, ...$attributes];
    }

    private function getFieldMapping(\ReflectionProperty $property): array
    {
        $meta = $this->entityManager->getClassMetadata($property->getDeclaringClass()->getName());
        $mappings = array_merge($meta->fieldMappings, $meta->associationMappings);
        if (! isset($mappings[$property->getName()])) {
            return [];
        }

        return $mappings[$property->getName()];
    }

    private function isAttribute(mixed $x): bool
    {
        return $x instanceof \ReflectionAttribute;
    }

    private function getClass(mixed $x): ?string
    {
        if ($this->isAttribute($x)) {
            return $x->getName();
        }

        return get_class($x);
    }

    private function getType(mixed $x, \ReflectionProperty $property): null|string|int
    {
        return $this->getXYZ($x, $property, 'type');
    }

    private function getTargetEntity(mixed $x, \ReflectionProperty $property): null|string|int
    {
        return $this->getXYZ($x, $property, 'targetEntity');
    }

    private function getXYZ(mixed $x, \ReflectionProperty $property, string $what): null|string|int
    {
        if ($this->isAttribute($x)) {
            $arguments = $x->getArguments();
            if (isset($arguments[$what])) {
                return $arguments[$what];
            }
        }

        if (! $this->isAttribute($x)) {
            if (property_exists($x, $what)) {
                return $x->{$what};
            }
        }

        $fieldMappings = $this->getFieldMapping($property);
        if (isset($fieldMappings[$what])) {
            return $fieldMappings[$what];
        }

        return null;
    }
}
