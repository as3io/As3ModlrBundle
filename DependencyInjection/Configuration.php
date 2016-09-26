<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Validates and merges configuration for Modlr.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('as3_modlr')
            ->children()
                ->append($this->getAdapterNode())
                ->append($this->getMetadataNode())
                ->append($this->getPersistersNode())
                ->append($this->getRestNode())
                ->append($this->getSchemaIndicesNode())
                ->append($this->getSearchClientsNode())
            ->end()
        ;
        return $treeBuilder;
    }

    /**
     * Creates a root config node with the provided key.
     *
     * @param   string $key
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function createRootNode($key)
    {
        $treeBuilder = new TreeBuilder();
        return $treeBuilder->root($key);
    }

    /**
     * Formats the root REST endpoint.
     *
     * @param   string  $endpoint
     * @return  string
     */
    private function formatRestEndpoint($endpoint)
    {
        return sprintf('%s', trim($endpoint, '/'));
    }

    /**
     * Gets the api adapter configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getAdapterNode()
    {
        $node = $this->createRootNode('adapter');
        return $node
            ->isRequired()
            ->addDefaultsIfNotSet()
            ->children()
                ->enumNode('type')
                    ->values(['jsonapiorg', null])
                ->end()
                ->scalarNode('service')->cannotBeEmpty()->end()
            ->end()
            ->validate()
                ->always(function($v) {
                    $this->validateAdapter($v);
                    return $v;
                })
            ->end()
        ;
    }

    /**
     * Gets the metadata configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getMetadataNode()
    {
        $node = $this->createRootNode('metadata');
        return $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('drivers')
                    ->defaultValue(['default' => ['type' => 'yml']])
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->children()

                            ->enumNode('type')->defaultValue('yml')
                                ->values(['yml', null])
                            ->end()
                            ->scalarNode('service')->cannotBeEmpty()->end()

                            ->arrayNode('parameters')
                                ->performNoDeepMerging()
                                ->prototype('variable')->end()
                            ->end()

                        ->end()
                    ->end()
                    ->validate()
                        ->always(function($v) {
                            $this->validateMetadataDrivers($v);
                            return $v;
                        })
                    ->end()
                ->end()

                ->arrayNode('cache')
                    ->canBeDisabled()
                    ->children()

                        ->enumNode('type')->defaultValue('file')
                            ->values(['file', 'binary_file', 'redis', null])
                        ->end()
                        ->scalarNode('service')->cannotBeEmpty()->end()

                        ->arrayNode('parameters')
                            ->performNoDeepMerging()
                            ->prototype('variable')->end()
                        ->end()

                    ->end()

                    ->validate()
                        ->always(function($v) {
                            $this->validateMetadataCache($v);
                            return $v;
                        })
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Gets the persisters configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getPersistersNode()
    {
        $node = $this->createRootNode('persisters');
        return $node
            ->isRequired()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()

                    ->enumNode('type')
                        ->values(['mongodb', null])
                    ->end()
                    ->scalarNode('service')->cannotBeEmpty()->end()

                    ->arrayNode('parameters')
                        ->performNoDeepMerging()
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always(function($v) {
                    $this->validatePersisters($v);
                    return $v;
                })
            ->end()
        ;
    }

    /**
     * Gets the rest configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getRestNode()
    {
        $node = $this->createRootNode('rest');
        return $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('root_endpoint')->isRequired()->cannotBeEmpty()->defaultValue('modlr/api')
                    ->validate()
                        ->always(function($v) {
                            $v = $this->formatRestEndpoint($v);
                            return $v;
                        })
                    ->end()
                ->end()
                ->booleanNode('debug')->defaultValue(false)->end()
            ->end()

        ;
    }

    /**
     * Gets the schema indices configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getSchemaIndicesNode()
    {
        $node = $this->createRootNode('schema_indices');
        return $node
            ->prototype('array')
                ->treatNullLike([])
                ->children()
                    ->scalarNode('model_type')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('name')->end()
                    ->arrayNode('keys')
                        ->performNoDeepMerging()
                        ->cannotBeEmpty()
                        ->prototype('variable')->end()
                    ->end()
                    ->arrayNode('options')
                        ->performNoDeepMerging()
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always(function($v) {
                    $v = $this->validateSchemaIndices($v);
                    return $v;
                })
            ->end()
        ;
    }

    /**
     * Gets the search clients configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getSearchClientsNode()
    {
        $node = $this->createRootNode('search_clients');
        return $node
            ->isRequired()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()

                    ->enumNode('type')
                        ->values(['elastic', null])
                    ->end()
                    ->scalarNode('service')->cannotBeEmpty()->end()

                    ->arrayNode('parameters')
                        ->performNoDeepMerging()
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always(function($v) {
                    $this->validateSearchClients($v);
                    return $v;
                })
            ->end()
        ;
    }

    /**
     * Validates the api adapter config.
     *
     * @param   array   $adapter
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateAdapter(array $adapter)
    {
        $this->validateTypeAndService($adapter, 'as3_modlr.adapter');
        switch ($adapter['type']) {
            case 'jsonapiorg':
                $this->validateLibClassExists('Api\JsonApiOrg\Adapter', 'as3_modlr.adapter.type');
                break;
            default:
                break;
        }
        return $this;
    }

    /**
     * Validates that a library class name exists.
     *
     * @param   string  $subClass
     * @param   string  $path
     * @throws  InvalidConfigurationException
     */
    private function validateLibClassExists($subClass, $path)
    {
        $class = Utility::getLibraryClass($subClass);
        if (false === class_exists($class)) {
            throw new InvalidConfigurationException(sprintf('The library class "%s" was not found for "%s" - was the library installed?', $class, $path));
        }
    }

    /**
     * Validates the metadata cache config.
     *
     * @param   array   $config
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateMetadataCache(array $config)
    {
        if (false === $config['enabled']) {
            return $this;
        }

        $this->validateTypeAndService($config, 'as3_modlr.metadata.cache');
        switch ($config['type']) {
            case 'redis':
                $this->validateMetadataCacheRedis($config);
                break;
            default:
                break;
        }
        return $this;
    }

    /**
     * Validates the Redis metadata cache config.
     *
     * @param   array   $config
     * @throws  InvalidConfigurationException
     */
    private function validateMetadataCacheRedis(array $config)
    {
        if (!isset($config['parameters']['handler'])) {
            throw new InvalidConfigurationException('A Redis handler service name must be defined for "as3_modlr.metadata.cache.parameters.handler"');
        }
    }

    /**
     * Validates the metadata drivers config.
     *
     * @param   array   $drivers
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateMetadataDrivers(array $drivers)
    {
        foreach ($drivers as $name => $config) {
            $this->validateTypeAndService($config, sprintf('as3_modlr.metadata.drivers.%s', $name));
        }
        return $this;
    }

    /**
     * Validates the MongoDb persister config.
     *
     * @param   array   $config
     * @throws  InvalidConfigurationException
     */
    private function validatePersisterMongoDb(array $config)
    {
        if (!isset($config['parameters']['host'])) {
            throw new InvalidConfigurationException(sprintf('The MongoDB persister requires a value for "as3_modlr.persisters.%s.parameters.host"', $name));
        }
    }

    /**
     * Validates the persisters config.
     *
     * @param   array   $persisters
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validatePersisters(array $persisters)
    {
        foreach ($persisters as $name => $config) {
            $this->validateTypeAndService($config, sprintf('as3_modlr.persisters.%s', $name));
            switch ($config['type']) {
                case 'mongodb':
                    $this->validateLibClassExists('Persister\MongoDb\Persister', sprintf('as3_modlr.persisters.%s.type', $name));
                    $this->validatePersisterMongoDb($config);
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Validates the schema indices config.
     *
     * @param   array   $indices
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateSchemaIndices(array $indices)
    {
        foreach ($indices as $k => $index) {
            // Set default name, if not present.
            if (!isset($index['name'])) {
                $name = 'modlr_';
                foreach ($index['keys'] as $key => $value) {
                    $name .= sprintf('_%s', $key);
                    if (is_numeric($value)) {
                        $name .= sprintf('_%s', $value);
                    }
                }
                $indices[$k]['name'] = $name;
            }
            if (empty($index['keys'])) {
                throw new InvalidConfigurationException(sprintf('At least one key must be specified to define an index at "as3_modlr.schema_indices.%s.keys"!', $k));
            }
        }
        return $indices;
    }

    /**
     * Validates the search clients config.
     *
     * @param   array   $clients
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateSearchClients(array $clients)
    {
        foreach ($clients as $name => $config) {
            $this->validateTypeAndService($config, sprintf('as3_modlr.search_clients.%s', $name));
            switch ($config['type']) {
                case 'elastic':
                    $this->validateLibClassExists('Search\Elastic\Client', sprintf('as3_modlr.search_clients.%s.type', $name));
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * Validates a configuration that uses type and service as options.
     *
     * @param   array   $config
     * @param   string  $path
     * @throws  InvalidArgumentException
     */
    private function validateTypeAndService(array $config, $path)
    {
        if (!isset($config['type']) && !isset($config['service'])) {
            throw new InvalidConfigurationException(sprintf('You must set one of "type" or "service" for "%s"', $path));
        }
        if (isset($config['type']) && isset($config['service'])) {
            throw new InvalidConfigurationException(sprintf('You cannot set both "type" and "service" for "%s" - please choose one.', $path));
        }
    }
}
