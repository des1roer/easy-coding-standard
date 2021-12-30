<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20211230\Symfony\Component\Config\Definition\Builder;

/**
 * An interface that must be implemented by nodes which can have children.
 *
 * @author Victor Berchet <victor@suumit.com>
 */
interface ParentNodeDefinitionInterface extends \ECSPrefix20211230\Symfony\Component\Config\Definition\Builder\BuilderAwareInterface
{
    /**
     * Returns a builder to add children nodes.
     */
    public function children() : \ECSPrefix20211230\Symfony\Component\Config\Definition\Builder\NodeBuilder;
    /**
     * Appends a node definition.
     *
     * Usage:
     *
     *     $node = $parentNode
     *         ->children()
     *             ->scalarNode('foo')->end()
     *             ->scalarNode('baz')->end()
     *             ->append($this->getBarNodeDefinition())
     *         ->end()
     *     ;
     *
     * @return $this
     */
    public function append(\ECSPrefix20211230\Symfony\Component\Config\Definition\Builder\NodeDefinition $node);
    /**
     * Gets the child node definitions.
     *
     * @return NodeDefinition[]
     */
    public function getChildNodeDefinitions() : array;
}
