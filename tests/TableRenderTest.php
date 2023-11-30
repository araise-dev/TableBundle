<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests;

use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Tests\App\Entity\Company;
use araise\TableBundle\Tests\App\Factory\CompanyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TableRenderTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testRenderDoctrineTableWithTurbo()
    {
        CompanyFactory::createMany(10);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $twig = self::getContainer()->get(Environment::class);
        if ($twig instanceof Environment) {
            $twig->addGlobal('enable_turbo', true);
        }

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->attributes->set('_route', 'dummy');

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 5,
        ]);

        $this->assertSame(
            [1, 2, 3, 4, 5],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );

        $twig = self::getContainer()->get(Environment::class);

        $twig->render('table.html.twig', [
            'table' => $table,
        ]);
    }

    public function testRenderDoctrineTableWithoutTurbo()
    {
        CompanyFactory::createMany(10);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $twig = self::getContainer()->get(Environment::class);
        if ($twig instanceof Environment) {
            $twig->addGlobal('enable_turbo', false);
        }

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->attributes->set('_route', 'dummy');

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 5,
        ]);

        $this->assertSame(
            [1, 2, 3, 4, 5],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );

        $twig = self::getContainer()->get(Environment::class);

        $twig->render('table.html.twig', [
            'table' => $table,
        ]);
    }
}
