<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests\App\Factory;

use araise\TableBundle\Tests\App\Entity\Company;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Company>
 */
final class CompanyFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Company::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
            'city' => self::faker()->city(),
            'country' => self::faker()->country(),
            'taxIdentificationNumber' => self::faker()->numerify(self::faker()->countryCode().'###.####.###.#.###.##'),
        ];
    }
}
