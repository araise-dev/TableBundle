<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests;

use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Table\Column;
use araise\TableBundle\Table\Table;
use araise\TableBundle\Tests\App\Entity\Company;
use araise\TableBundle\Tests\App\Factory\CompanyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ColumnTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testColumnPriorityDefault()
    {
        $table = $this->prepareTable();

        $table->addColumn('name')
            ->addColumn('city')
            ->addColumn('country')
            ->addColumn('taxIdentificationNumber')
        ;

        $columns = $table->getColumns();

        $this->assertSame(0, array_search('name', array_keys($columns)));
        $this->assertSame(1, array_search('city', array_keys($columns)));
        $this->assertSame(2, array_search('country', array_keys($columns)));
        $this->assertSame(3, array_search('taxIdentificationNumber', array_keys($columns)));
    }

    public function testColumnPriorityReorder()
    {
        $table = $this->prepareTable();

        $table->addColumn('name', null, [
            Column::OPT_PRIORITY => 1,
        ])
            ->addColumn('city', null, [
                Column::OPT_PRIORITY => 2,
            ])
            ->addColumn('country', null, [
                Column::OPT_PRIORITY => 3,
            ])
            ->addColumn('taxIdentificationNumber', null, [
                Column::OPT_PRIORITY => 4,
            ])
        ;

        $columns = $table->getColumns();

        $this->assertSame(0, array_search('taxIdentificationNumber', array_keys($columns)));
        $this->assertSame(1, array_search('country', array_keys($columns)));
        $this->assertSame(2, array_search('city', array_keys($columns)));
        $this->assertSame(3, array_search('name', array_keys($columns)));
    }

    public function testColumnPrioritySameOrder()
    {
        $table = $this->prepareTable();

        $table->addColumn('name', null, [
            Column::OPT_PRIORITY => 1,
        ])
            ->addColumn('city', null, [
                Column::OPT_PRIORITY => 2,
            ])
            ->addColumn('country', null, [
                Column::OPT_PRIORITY => 2,
            ])
            ->addColumn('taxIdentificationNumber', null, [
                Column::OPT_PRIORITY => 4,
            ])
        ;

        $columns = $table->getColumns();

        $this->assertSame(0, array_search('taxIdentificationNumber', array_keys($columns)));
        $this->assertSame(1, array_search('city', array_keys($columns)));
        $this->assertSame(2, array_search('country', array_keys($columns)));
        $this->assertSame(3, array_search('name', array_keys($columns)));
    }

    protected function prepareTable(): Table
    {
        CompanyFactory::createMany(40);

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push($fakeRequest);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 10,
        ]);

        return $table;
    }
}
