<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[AutoconfigureTag('araise_table.filter_type')]
abstract class FilterType implements FilterTypeInterface
{
    /**
     * Define the column name to filter on.
     * Accepts: <code>string</code>
     */
    public const OPT_COLUMN = 'column';

    /**
     * Define the joins to use for the filter.
     * Defaults to an empty array.
     * Accepts: <code>array</code>
     */
    public const OPT_JOINS = 'joins';

    protected array $options = [];

    public function __construct(?string $column = null, array $joins = [])
    {
        $this->options = [
            self::OPT_COLUMN => $column,
            self::OPT_JOINS => $joins,
        ];
    }

    public function setOptions(array $options): static
    {
        $this->options = $this->getOptionsResolver()->resolve($options);
        return $this;
    }

    public function setOption(string $name, mixed $value): static
    {
        if (! $this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }
        $this->options[$name] = $value;
        return $this;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]) || array_key_exists($name, $this->options);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name): mixed
    {
        if (! $this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('Option "%s" for %s does not exist.', $name, static::class));
        }

        return $this->options[$name];
    }

    /**
     * @deprecated use getOption instead
     */
    public function getColumn(): string
    {
        return $this->getOption(self::OPT_COLUMN);
    }

    /**
     * @deprecated use setOption instead
     */
    public function setColumn($column): static
    {
        return $this->setOption(self::OPT_COLUMN, $column);
    }

    /**
     * @deprecated use getOption instead
     */
    public function getJoins(): array
    {
        return $this->getOption(self::OPT_JOINS);
    }

    /**
     * @deprecated use setOption instead
     */
    public function setJoins($joins): static
    {
        return $this->setOption(self::OPT_JOINS, $joins);
    }

    public function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        return $resolver;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPT_COLUMN => null,
            self::OPT_JOINS => [],
        ]);
        $resolver->setAllowedTypes(self::OPT_COLUMN, ['string']);
        $resolver->setAllowedTypes(self::OPT_JOINS, ['array']);
    }
}
