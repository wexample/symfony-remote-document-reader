<?php

namespace Wexample\SymfonyRemoteDocumentReader\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyRemoteDocumentReaderExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('wexample_symfony_remote_document_reader.url', $config['url']);

        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
