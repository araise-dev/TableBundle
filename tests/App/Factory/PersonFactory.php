<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests\App\Factory;

use araise\TableBundle\Tests\App\Entity\Person;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Person>
 */
final class PersonFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Person::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->name(),
        ];
    }
}
