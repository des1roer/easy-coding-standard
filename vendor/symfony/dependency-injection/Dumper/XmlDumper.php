<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20220124\Symfony\Component\DependencyInjection\Dumper;

use ECSPrefix20220124\Symfony\Component\DependencyInjection\Alias;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Definition;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Exception\RuntimeException;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Parameter;
use ECSPrefix20220124\Symfony\Component\DependencyInjection\Reference;
use ECSPrefix20220124\Symfony\Component\ExpressionLanguage\Expression;
/**
 * XmlDumper dumps a service container as an XML string.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Martin Hasoň <martin.hason@gmail.com>
 */
class XmlDumper extends \ECSPrefix20220124\Symfony\Component\DependencyInjection\Dumper\Dumper
{
    /**
     * @var \DOMDocument
     */
    private $document;
    /**
     * Dumps the service container as an XML string.
     */
    public function dump(array $options = []) : string
    {
        $this->document = new \DOMDocument('1.0', 'utf-8');
        $this->document->formatOutput = \true;
        $container = $this->document->createElementNS('http://symfony.com/schema/dic/services', 'container');
        $container->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $container->setAttribute('xsi:schemaLocation', 'http://symfony.com/schema/dic/services https://symfony.com/schema/dic/services/services-1.0.xsd');
        $this->addParameters($container);
        $this->addServices($container);
        $this->document->appendChild($container);
        $xml = $this->document->saveXML();
        unset($this->document);
        return $this->container->resolveEnvPlaceholders($xml);
    }
    private function addParameters(\DOMElement $parent)
    {
        $data = $this->container->getParameterBag()->all();
        if (!$data) {
            return;
        }
        if ($this->container->isCompiled()) {
            $data = $this->escape($data);
        }
        $parameters = $this->document->createElement('parameters');
        $parent->appendChild($parameters);
        $this->convertParameters($data, 'parameter', $parameters);
    }
    private function addMethodCalls(array $methodcalls, \DOMElement $parent)
    {
        foreach ($methodcalls as $methodcall) {
            $call = $this->document->createElement('call');
            $call->setAttribute('method', $methodcall[0]);
            if (\count($methodcall[1])) {
                $this->convertParameters($methodcall[1], 'argument', $call);
            }
            if ($methodcall[2] ?? \false) {
                $call->setAttribute('returns-clone', 'true');
            }
            $parent->appendChild($call);
        }
    }
    private function addService(\ECSPrefix20220124\Symfony\Component\DependencyInjection\Definition $definition, ?string $id, \DOMElement $parent)
    {
        $service = $this->document->createElement('service');
        if (null !== $id) {
            $service->setAttribute('id', $id);
        }
        if ($class = $definition->getClass()) {
            if ('\\' === \substr($class, 0, 1)) {
                $class = \substr($class, 1);
            }
            $service->setAttribute('class', $class);
        }
        if (!$definition->isShared()) {
            $service->setAttribute('shared', 'false');
        }
        if ($definition->isPublic()) {
            $service->setAttribute('public', 'true');
        }
        if ($definition->isSynthetic()) {
            $service->setAttribute('synthetic', 'true');
        }
        if ($definition->isLazy()) {
            $service->setAttribute('lazy', 'true');
        }
        if (null !== ($decoratedService = $definition->getDecoratedService())) {
            [$decorated, $renamedId, $priority] = $decoratedService;
            $service->setAttribute('decorates', $decorated);
            $decorationOnInvalid = $decoratedService[3] ?? \ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
            if (\in_array($decorationOnInvalid, [\ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface::IGNORE_ON_INVALID_REFERENCE, \ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE], \true)) {
                $invalidBehavior = \ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE === $decorationOnInvalid ? 'null' : 'ignore';
                $service->setAttribute('decoration-on-invalid', $invalidBehavior);
            }
            if (null !== $renamedId) {
                $service->setAttribute('decoration-inner-name', $renamedId);
            }
            if (0 !== $priority) {
                $service->setAttribute('decoration-priority', $priority);
            }
        }
        foreach ($definition->getTags() as $name => $tags) {
            foreach ($tags as $attributes) {
                $tag = $this->document->createElement('tag');
                if (!\array_key_exists('name', $attributes)) {
                    $tag->setAttribute('name', $name);
                } else {
                    $tag->appendChild($this->document->createTextNode($name));
                }
                foreach ($attributes as $key => $value) {
                    $tag->setAttribute($key, $value ?? '');
                }
                $service->appendChild($tag);
            }
        }
        if ($definition->getFile()) {
            $file = $this->document->createElement('file');
            $file->appendChild($this->document->createTextNode($definition->getFile()));
            $service->appendChild($file);
        }
        if ($parameters = $definition->getArguments()) {
            $this->convertParameters($parameters, 'argument', $service);
        }
        if ($parameters = $definition->getProperties()) {
            $this->convertParameters($parameters, 'property', $service, 'name');
        }
        $this->addMethodCalls($definition->getMethodCalls(), $service);
        if ($callable = $definition->getFactory()) {
            $factory = $this->document->createElement('factory');
            if (\is_array($callable) && $callable[0] instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Definition) {
                $this->addService($callable[0], null, $factory);
                $factory->setAttribute('method', $callable[1]);
            } elseif (\is_array($callable)) {
                if (null !== $callable[0]) {
                    $factory->setAttribute($callable[0] instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Reference ? 'service' : 'class', $callable[0]);
                }
                $factory->setAttribute('method', $callable[1]);
            } else {
                $factory->setAttribute('function', $callable);
            }
            $service->appendChild($factory);
        }
        if ($definition->isDeprecated()) {
            $deprecation = $definition->getDeprecation('%service_id%');
            $deprecated = $this->document->createElement('deprecated');
            $deprecated->appendChild($this->document->createTextNode($definition->getDeprecation('%service_id%')['message']));
            $deprecated->setAttribute('package', $deprecation['package']);
            $deprecated->setAttribute('version', $deprecation['version']);
            $service->appendChild($deprecated);
        }
        if ($definition->isAutowired()) {
            $service->setAttribute('autowire', 'true');
        }
        if ($definition->isAutoconfigured()) {
            $service->setAttribute('autoconfigure', 'true');
        }
        if ($definition->isAbstract()) {
            $service->setAttribute('abstract', 'true');
        }
        if ($callable = $definition->getConfigurator()) {
            $configurator = $this->document->createElement('configurator');
            if (\is_array($callable) && $callable[0] instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Definition) {
                $this->addService($callable[0], null, $configurator);
                $configurator->setAttribute('method', $callable[1]);
            } elseif (\is_array($callable)) {
                $configurator->setAttribute($callable[0] instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Reference ? 'service' : 'class', $callable[0]);
                $configurator->setAttribute('method', $callable[1]);
            } else {
                $configurator->setAttribute('function', $callable);
            }
            $service->appendChild($configurator);
        }
        $parent->appendChild($service);
    }
    private function addServiceAlias(string $alias, \ECSPrefix20220124\Symfony\Component\DependencyInjection\Alias $id, \DOMElement $parent)
    {
        $service = $this->document->createElement('service');
        $service->setAttribute('id', $alias);
        $service->setAttribute('alias', $id);
        if ($id->isPublic()) {
            $service->setAttribute('public', 'true');
        }
        if ($id->isDeprecated()) {
            $deprecation = $id->getDeprecation('%alias_id%');
            $deprecated = $this->document->createElement('deprecated');
            $deprecated->appendChild($this->document->createTextNode($deprecation['message']));
            $deprecated->setAttribute('package', $deprecation['package']);
            $deprecated->setAttribute('version', $deprecation['version']);
            $service->appendChild($deprecated);
        }
        $parent->appendChild($service);
    }
    private function addServices(\DOMElement $parent)
    {
        $definitions = $this->container->getDefinitions();
        if (!$definitions) {
            return;
        }
        $services = $this->document->createElement('services');
        foreach ($definitions as $id => $definition) {
            $this->addService($definition, $id, $services);
        }
        $aliases = $this->container->getAliases();
        foreach ($aliases as $alias => $id) {
            while (isset($aliases[(string) $id])) {
                $id = $aliases[(string) $id];
            }
            $this->addServiceAlias($alias, $id, $services);
        }
        $parent->appendChild($services);
    }
    private function convertParameters(array $parameters, string $type, \DOMElement $parent, string $keyAttribute = 'key')
    {
        $withKeys = !array_is_list($parameters);
        foreach ($parameters as $key => $value) {
            $element = $this->document->createElement($type);
            if ($withKeys) {
                $element->setAttribute($keyAttribute, $key);
            }
            if (\is_array($tag = $value)) {
                $element->setAttribute('type', 'collection');
                $this->convertParameters($value, $type, $element, 'key');
            } elseif ($value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument || $value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument && ($tag = $value->getTaggedIteratorArgument())) {
                $element->setAttribute('type', $value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument ? 'tagged_iterator' : 'tagged_locator');
                $element->setAttribute('tag', $tag->getTag());
                if (null !== $tag->getIndexAttribute()) {
                    $element->setAttribute('index-by', $tag->getIndexAttribute());
                    if (null !== $tag->getDefaultIndexMethod()) {
                        $element->setAttribute('default-index-method', $tag->getDefaultIndexMethod());
                    }
                    if (null !== $tag->getDefaultPriorityMethod()) {
                        $element->setAttribute('default-priority-method', $tag->getDefaultPriorityMethod());
                    }
                }
            } elseif ($value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\IteratorArgument) {
                $element->setAttribute('type', 'iterator');
                $this->convertParameters($value->getValues(), $type, $element, 'key');
            } elseif ($value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument) {
                $element->setAttribute('type', 'service_locator');
                $this->convertParameters($value->getValues(), $type, $element, 'key');
            } elseif ($value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Reference || $value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument) {
                $element->setAttribute('type', 'service');
                if ($value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument) {
                    $element->setAttribute('type', 'service_closure');
                    $value = $value->getValues()[0];
                }
                $element->setAttribute('id', (string) $value);
                $behavior = $value->getInvalidBehavior();
                if (\ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE == $behavior) {
                    $element->setAttribute('on-invalid', 'null');
                } elseif (\ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface::IGNORE_ON_INVALID_REFERENCE == $behavior) {
                    $element->setAttribute('on-invalid', 'ignore');
                } elseif (\ECSPrefix20220124\Symfony\Component\DependencyInjection\ContainerInterface::IGNORE_ON_UNINITIALIZED_REFERENCE == $behavior) {
                    $element->setAttribute('on-invalid', 'ignore_uninitialized');
                }
            } elseif ($value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Definition) {
                $element->setAttribute('type', 'service');
                $this->addService($value, null, $element);
            } elseif ($value instanceof \ECSPrefix20220124\Symfony\Component\ExpressionLanguage\Expression) {
                $element->setAttribute('type', 'expression');
                $text = $this->document->createTextNode(self::phpToXml((string) $value));
                $element->appendChild($text);
            } elseif (\is_string($value) && !\preg_match('/^[^\\x00-\\x08\\x0B\\x0E-\\x1A\\x1C-\\x1F\\x7F]*+$/u', $value)) {
                $element->setAttribute('type', 'binary');
                $text = $this->document->createTextNode(self::phpToXml(\base64_encode($value)));
                $element->appendChild($text);
            } elseif ($value instanceof \ECSPrefix20220124\UnitEnum) {
                $element->setAttribute('type', 'constant');
                $element->appendChild($this->document->createTextNode(self::phpToXml($value)));
            } elseif ($value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Argument\AbstractArgument) {
                $element->setAttribute('type', 'abstract');
                $text = $this->document->createTextNode(self::phpToXml($value->getText()));
                $element->appendChild($text);
            } else {
                if (\in_array($value, ['null', 'true', 'false'], \true)) {
                    $element->setAttribute('type', 'string');
                }
                if (\is_string($value) && (\is_numeric($value) || \preg_match('/^0b[01]*$/', $value) || \preg_match('/^0x[0-9a-f]++$/i', $value))) {
                    $element->setAttribute('type', 'string');
                }
                $text = $this->document->createTextNode(self::phpToXml($value));
                $element->appendChild($text);
            }
            $parent->appendChild($element);
        }
    }
    /**
     * Escapes arguments.
     */
    private function escape(array $arguments) : array
    {
        $args = [];
        foreach ($arguments as $k => $v) {
            if (\is_array($v)) {
                $args[$k] = $this->escape($v);
            } elseif (\is_string($v)) {
                $args[$k] = \str_replace('%', '%%', $v);
            } else {
                $args[$k] = $v;
            }
        }
        return $args;
    }
    /**
     * Converts php types to xml types.
     *
     * @throws RuntimeException When trying to dump object or resource
     * @param mixed $value
     */
    public static function phpToXml($value) : string
    {
        switch (\true) {
            case null === $value:
                return 'null';
            case \true === $value:
                return 'true';
            case \false === $value:
                return 'false';
            case $value instanceof \ECSPrefix20220124\Symfony\Component\DependencyInjection\Parameter:
                return '%' . $value . '%';
            case $value instanceof \ECSPrefix20220124\UnitEnum:
                return \sprintf('%s::%s', \get_class($value), $value->name);
            case \is_object($value) || \is_resource($value):
                throw new \ECSPrefix20220124\Symfony\Component\DependencyInjection\Exception\RuntimeException('Unable to dump a service container if a parameter is an object or a resource.');
            default:
                return (string) $value;
        }
    }
}
