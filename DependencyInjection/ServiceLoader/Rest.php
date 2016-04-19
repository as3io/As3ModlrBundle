<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads RESTful API services.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class Rest implements ServiceLoaderInterface
{
    /**
     * Creates the jsonapi.org Adapter service definition.
     *
     * @param   string              $configName
     * @param   ContainerBuilder    $container
     * @return  Definition
     */
    private function createJsonApiAdapter($configName, ContainerBuilder $container)
    {
        // Serializer
        $serializerName = Utility::getAliasedName('api.serializer');
        $definition = new Definition(
            Utility::getLibraryClass('Api\JsonApiOrg\Serializer')
        );
        $definition->setPublic(false);
        $container->setDefinition($serializerName, $definition);

        // Normalizer
        $normalizerName = Utility::getAliasedName('api.normalizer');
        $definition = new Definition(
            Utility::getLibraryClass('Api\JsonApiOrg\Normalizer')
        );
        $definition->setPublic(false);
        $container->setDefinition($normalizerName, $definition);

        // Adapter
        $definition = new Definition(
            Utility::getLibraryClass('Api\JsonApiOrg\Adapter'),
            [
                new Reference($serializerName),
                new Reference($normalizerName),
                new Reference(Utility::getAliasedName('store')),
                new Reference($configName),
            ]
        );
        $definition->setPublic(true);
        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $adapterName = Utility::getAliasedName('api.adapter');
        $configName  = Utility::getAliasedName('rest.configuration');

        $this->loadConfiguration($configName, $config['rest'], $container);
        $this->loadAdapter($adapterName, $configName, $config['adapter'], $container);
        $this->loadKernel($adapterName, $configName, $config['rest']['debug'], $container);
        return $this;
    }

    /**
     * Loads the Adapter service based on the adapter config.
     *
     * @param   string              $adapterName
     * @param   string              $configName
     * @param   array               $adapterConfig
     * @param   ContainerBuilder    $container
     * @return  self
     */
    private function loadAdapter($adapterName, $configName, array $adapterConfig, ContainerBuilder $container)
    {
        if (isset($adapterConfig['service'])) {
            // Custom adapter service.
            $container->setAlias($adapterName, Utility::cleanServiceName($adapterConfig['service']));
            return $this;
        }

        // Built-In Adapter
        switch ($adapterConfig['type']) {
            case 'jsonapiorg':
                $definition = $this->createJsonApiAdapter($configName, $container);
                break;
            default:
                throw new \RuntimeException(sprintf('The adapter type "%s" is currently not supported.', $adapterConfig['type']));
        }
        $container->setDefinition($adapterName, $definition);
        return $this;

    }

    /**
     * Loads the Rest config service based on the config.
     *
     * @param   string              $name
     * @param   array               $restConfig
     * @param   ContainerBuilder    $container
     * @return  self
     */
    private function loadConfiguration($name, array $restConfig, ContainerBuilder $container)
    {
        $definition = new Definition(
            Utility::getLibraryClass('Rest\RestConfiguration'),
            [
                new Reference(Utility::getAliasedName('util.validator')),
            ]
        );
        $definition->setPublic(false);

        $endpoint = $restConfig['root_endpoint'];
        $definition->addMethodCall('setRootEndpoint', [$endpoint]);

        $container->setDefinition($name, $definition);
        $container->setParameter(Utility::getAliasedName('rest.root_endpoint'), $endpoint);
        return $this;
    }

    /**
     * Loads the Rest Kernel service based on the config.
     *
     * @param   string              $adapterName
     * @param   string              $configName
     * @param   bool                $debug
     * @param   ContainerBuilder    $container
     * @return  self
     */
    private function loadKernel($adapterName, $configName, $debug, ContainerBuilder $container)
    {
        $definition = new Definition(
            Utility::getLibraryClass('Rest\RestKernel'),
            [
                new Reference($adapterName),
                new Reference($configName),
            ]
        );
        $definition->addMethodCall('enableDebug', [$debug]);
        $container->setDefinition(Utility::getAliasedName('rest.kernel'), $definition);
    }
}
