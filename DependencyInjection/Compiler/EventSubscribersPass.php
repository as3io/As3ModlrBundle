<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\Compiler;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds event subscribers to the event dispatcher.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class EventSubscribersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dispatcher = $container->getDefinition(Utility::getAliasedName('event_dispatcher'));

        $sortFunc = function ($a, $b) {
            $a = isset($a['priority']) ? (Integer) $a['priority'] : 0;
            $b = isset($b['priority']) ? (Integer) $b['priority'] : 0;

            return $a > $b ? -1 : 1;
        };

        $tagged = $container->findTaggedServiceIds(Utility::getAliasedName('event_subscriber'));
        $subscribers = [];
        foreach ($tagged as $id => $tags) {
            foreach ($tags as $attributes) {
                $subscribers[$id] = $attributes;
            }
        }
        uasort($subscribers, $sortFunc);
        foreach ($subscribers as $id => $attrs) {
            $dispatcher->addMethodCall('addSubscriber', [new Reference($id)]);
        }
    }
}
