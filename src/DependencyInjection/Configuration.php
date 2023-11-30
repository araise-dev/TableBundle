<?php

declare(strict_types=1);

namespace araise\TableBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Configuration implements ConfigurationInterface
{
    public function __construct(
        protected ContainerBuilder $containerBuilder
    ) {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('araise_table');
        $rootNode = $treeBuilder->getRootNode();

        if (!method_exists($rootNode, 'children')) {
            throw new \RuntimeException('Expected configuration rootNode to have method children()');
        }

        $coreConfig = $this->containerBuilder->getParameter('araise_core.enable_turbo');

        $rootNode
            ->children()
                ->booleanNode('enable_turbo')
                ->defaultValue($coreConfig)
                ->end();

        return $treeBuilder;
    }
}
