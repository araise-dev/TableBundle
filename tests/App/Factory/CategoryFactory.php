<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests\App\Factory;

use araise\TableBundle\Tests\App\Entity\Category;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Category>
 */
final class CategoryFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Category::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->company(),
        ];
    }
}
