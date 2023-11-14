<?php

declare(strict_types=1);

namespace araise\TableBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class araiseTableExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('araise.enable_turbo', $config['enable_turbo']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('araise_core')) {
            return;
        }
        $configs = $container->getExtensionConfig($this->getAlias());

        foreach (array_reverse($configs) as $config) {
            if (isset($config['enable_turbo'])) {
                $container->prependExtensionConfig('araise_core', [
                    'enable_turbo' => $config['enable_turbo'],
                ]);
            }
        }

        if (! $container->hasExtension('doctrine_migrations')) {
            return;
        }

        $doctrineConfig = $container->getExtensionConfig('doctrine_migrations');
        $container->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => array_merge(
                array_pop($doctrineConfig)['migrations_paths'] ?? [],
                [
                    'araise\TableBundle\Migrations' => '@araiseTableBundle/Migrations',
                ]
            ),
        ]);
    }
}
