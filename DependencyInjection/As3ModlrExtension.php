<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Loads and manages the bundle configuration for Modlr.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class As3ModlrExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Process the config.
        $config = $this->processConfiguration(new Configuration(), $configs);

        // Load bundle services.
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $serviceLoader = new ServiceLoaderManager($container);
        $serviceLoader->loadServices($config);
    }
}
