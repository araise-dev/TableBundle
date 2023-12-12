<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnumFilterType extends ChoiceFilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    /**
     * Defines the enum cases
     * Accepts: <code>array</code>
     * Default: <code>[]</code>
     */
    public const OPT_ENUM_CASES = 'enum_cases';

    public function __construct(
        protected ?TranslatorInterface $translator = null
    ) {
        parent::__construct();
    }

    public function getOption(string $name): mixed
    {
        $value = parent::getOption($name);

        if ($name === self::OPT_CHOICES) {
            $enumCases = parent::getOption(self::OPT_ENUM_CASES);
            $value = [];

            foreach ($enumCases as $case) {
                $value[$case->value] = $this->translator?->trans($case->value) ?? $case->value;
            }
        }

        return $value;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault(static::OPT_ENUM_CASES, []);
        $resolver->setAllowedTypes(static::OPT_ENUM_CASES, ['array']);
    }
}
