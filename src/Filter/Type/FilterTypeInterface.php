<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

interface FilterTypeInterface
{
    /**
     * @deprecated Use OPT_COLUMN instead
     */
    public function getColumn(): string;

    /**
     * @deprecated Use OPT_JOINS instead
     */
    public function getJoins(): array;

    public function getOperators(): array;

    public function getValueField(?string $value, ?string $operator): string;

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder);

    public function setOptions(array $options): static;

    public function setOption(string $name, mixed $value): static;

    public function hasOption(string $name): bool;

    public function getOptions(): array;

    public function getOption(string $name): mixed;
}
