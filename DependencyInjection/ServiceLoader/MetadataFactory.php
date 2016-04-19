<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads the metadata factory service.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class MetadataFactory implements ServiceLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $definition = new Definition(
            Utility::getLibraryClass('Metadata\MetadataFactory'),
            [
                new Reference(Utility::getAliasedName('metadata.default_driver')),
                new Reference(Utility::getAliasedName('util.entity')),
                new Reference(Utility::getAliasedName('event_dispatcher')),
            ]
        );

        if (true === $config['metadata']['cache']['enabled']) {
            $definition->addMethodCall('setCache', [new Reference(Utility::getAliasedName('metadata.cache'))]);
        }
        $container->setDefinition(Utility::getAliasedName('metadata.factory'), $definition);
        return $this;
    }
}
