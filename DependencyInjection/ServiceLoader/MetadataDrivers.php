<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads metadata driver services.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class MetadataDrivers implements ServiceLoaderInterface
{
    /**
     * Creates the file locator service definition.
     *
     * @param   string  $modelDir
     * @param   string  $mixinDir
     * @return  Definition
     */
    private function createFileLocator($modelDir, $mixinDir)
    {
        $definition = new Definition(
            Utility::getLibraryClass('Metadata\Driver\FileLocator'),
            [$modelDir, $mixinDir]
        );
        $definition->setPublic(false);
        return $definition;
    }

    /**
     * Creates the YAML metadata driver service definition.
     *
     * @param   string              $driverName
     * @param   array               $driverConfig
     * @param   ContainerBuilder    $container
     * @return  Definition
     */
    private function createYmlDriver($driverName, array $driverConfig, ContainerBuilder $container)
    {
        // Definition directories
        list($modelDir, $mixinDir) = $this->getDefinitionDirs($driverName, $driverConfig, $container);

        // Set the directories to the dirs container parameter.
        Utility::appendParameter('dirs', sprintf('%s.model_dir', $driverName), $modelDir, $container);
        Utility::appendParameter('dirs', sprintf('%s.mixin_dir', $driverName), $mixinDir, $container);

        // File locator
        $locatorName = sprintf('%s.file_locator', $driverName);
        $locatorDef = $this->createFileLocator($modelDir, $mixinDir);
        $container->setDefinition($locatorName, $locatorDef);

        // Driver
        return new Definition(
            Utility::getLibraryClass('Metadata\Driver\YamlFileDriver'),
            [
                new Reference($locatorName),
                new Reference(Utility::getAliasedName('storage_manager')),
            ]
        );
    }

    /**
     * Gets the definition directories for models and mixins and returns as a tuple.
     *
     * @param   string              $driverName
     * @param   array               $driverConfig
     * @param   ContainerBuilder    $container
     * @return  string[]
     */
    private function getDefinitionDirs($driverName, array $driverConfig, ContainerBuilder $container)
    {
        $defaultDir = sprintf('%s/Resources/As3ModlrBundle', $container->getParameter('kernel.root_dir'));

        $modelDir = sprintf('%s/models', $defaultDir);
        $mixinDir = sprintf('%s/mixins', $defaultDir);
        if (isset($driverConfig['parameters']['model_dir'])) {
            $modelDir = Utility::locateResource($driverConfig['parameters']['model_dir'], $container);
        }
        if (isset($driverConfig['parameters']['mixin_dir'])) {
            $mixinDir = Utility::locateResource($driverConfig['parameters']['mixin_dir'], $container);
        }

        return [$modelDir, $mixinDir];
    }

    /**
     * {@inheritdoc}
     * @todo    Add low-level support for multiple drivers in the metadata factory.
     */
    public function load(array $config, ContainerBuilder $container)
    {
        foreach ($config['metadata']['drivers'] as $name => $driverConfig) {
            $driverName = Utility::getAliasedName(sprintf('metadata.driver.%s', $name));
            if (isset($driverConfig['service'])) {
                // Custom persister.
                $container->setAlias($driverName, Utility::cleanServiceName($driverConfig['service']));
            } else {
                // Built-in driver.
                switch ($driverConfig['type']) {
                    case 'yml':
                        $definition = $this->createYmlDriver($driverName, $driverConfig, $container);
                        break;
                    default:
                        throw new \RuntimeException(sprintf('Unable to create a metadata driver for type "%s"', $driverConfig['type']));
                }
                $definition->setPublic(false);
                $container->setDefinition($driverName, $definition);
            }
            // The library currently only supports one driver. Must break and set as default alias.
            $container->setAlias(Utility::getAliasedName('metadata.default_driver'), $driverName);
            break;
        }
        return $this;
    }
}
