<?php

namespace whatwedo\TableBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Provider\QueryBuilderProvider;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class whatwedoTableExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->registerForAutoconfiguration(ExtensionInterface::class)->addTag('table.extension');
        $container->registerForAutoconfiguration(QueryBuilderProvider::class)->addTag('table_bundle.query_builder_provider');
    }
}
