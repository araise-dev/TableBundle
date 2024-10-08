<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests\App\Factory;

use araise\TableBundle\Tests\App\Entity\Contact;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Contact>
 */
final class ContactFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Contact::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->name(),
            'company' => CompanyFactory::randomOrCreate(),
        ];
    }
}
