<?php

namespace Wexample\SymfonyRemoteDocumentReader\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_remote_document_reader');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('url')->defaultNull()->info('Base URL of a document-reader service, e.g. http://document_reader:8000.')->end()
            ->end();

        return $treeBuilder;
    }
}
