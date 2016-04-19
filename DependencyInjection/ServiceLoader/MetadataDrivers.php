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
     * @param   string  $embedDir
     * @return  Definition
     */
    private function createFileLocator($modelDir, $mixinDir, $embedDir)
    {
        $definition = new Definition(
            Utility::getLibraryClass('Metadata\Driver\FileLocator'),
            [$modelDir, $mixinDir, $embedDir]
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
        list($modelDir, $mixinDir, $embedDir) = $this->getDefinitionDirs($driverConfig, $container);

        // Set the directories to the dirs container parameter.
        Utility::appendParameter('dirs', sprintf('%s.model_dir', $driverName), $modelDir, $container);
        Utility::appendParameter('dirs', sprintf('%s.mixin_dir', $driverName), $mixinDir, $container);
        Utility::appendParameter('dirs', sprintf('%s.embed_dir', $driverName), $embedDir, $container);

        // File locator
        $locatorName = sprintf('%s.file_locator', $driverName);
        $locatorDef = $this->createFileLocator($modelDir, $mixinDir, $embedDir);
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
     * Gets the directory for a definition type.
     *
     * @param   string              $type
     * @param   array               $driverConfig
     * @param   ContainerBuilder    $container
     * @return  string
     */
    private function getDefinitionDir($type, array $driverConfig, ContainerBuilder $container)
    {
        $defaultDir = sprintf('%s/Resources/As3ModlrBundle', $container->getParameter('kernel.root_dir'));

        $folder = sprintf('%ss', $type);
        $key = sprintf('%s_dir', $type);

        return isset($driverConfig['parameters'][$key])
            ? Utility::locateResource($driverConfig['parameters'][$key], $container)
            : sprintf('%s/%s', $defaultDir, $folder)
        ;
    }

    /**
     * Gets the definition directories for models and mixins and returns as a tuple.
     *
     * @param   array               $driverConfig
     * @param   ContainerBuilder    $container
     * @return  string[]
     */
    private function getDefinitionDirs(array $driverConfig, ContainerBuilder $container)
    {
        $modelDir = $this->getDefinitionDir('model', $driverConfig, $container);
        $mixinDir = $this->getDefinitionDir('mixin', $driverConfig, $container);
        $embedDir = $this->getDefinitionDir('embed', $driverConfig, $container);
        return [$modelDir, $mixinDir, $embedDir];
    }

    /**
     * {@inheritdoc}
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
