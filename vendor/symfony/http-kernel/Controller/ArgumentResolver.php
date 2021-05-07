<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210507\Symfony\Component\HttpKernel\Controller;

use ECSPrefix20210507\Symfony\Component\HttpFoundation\Request;
use ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestAttributeValueResolver;
use ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestValueResolver;
use ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\SessionValueResolver;
use ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\VariadicValueResolver;
use ECSPrefix20210507\Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use ECSPrefix20210507\Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactoryInterface;
/**
 * Responsible for resolving the arguments passed to an action.
 *
 * @author Iltar van der Berg <kjarli@gmail.com>
 */
final class ArgumentResolver implements \ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface
{
    private $argumentMetadataFactory;
    /**
     * @var iterable|ArgumentValueResolverInterface[]
     */
    private $argumentValueResolvers;
    /**
     * @param mixed[] $argumentValueResolvers
     * @param \ECSPrefix20210507\Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactoryInterface $argumentMetadataFactory
     */
    public function __construct($argumentMetadataFactory = null, $argumentValueResolvers = [])
    {
        $this->argumentMetadataFactory = isset($argumentMetadataFactory) ? $argumentMetadataFactory : new \ECSPrefix20210507\Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory();
        $this->argumentValueResolvers = $argumentValueResolvers ?: self::getDefaultArgumentValueResolvers();
    }
    /**
     * {@inheritdoc}
     * @param \ECSPrefix20210507\Symfony\Component\HttpFoundation\Request $request
     * @return mixed[]
     */
    public function getArguments($request, callable $controller)
    {
        $arguments = [];
        foreach ($this->argumentMetadataFactory->createArgumentMetadata($controller) as $metadata) {
            foreach ($this->argumentValueResolvers as $resolver) {
                if (!$resolver->supports($request, $metadata)) {
                    continue;
                }
                $resolved = $resolver->resolve($request, $metadata);
                $atLeastOne = \false;
                foreach ($resolved as $append) {
                    $atLeastOne = \true;
                    $arguments[] = $append;
                }
                if (!$atLeastOne) {
                    throw new \InvalidArgumentException(\sprintf('"%s::resolve()" must yield at least one value.', \get_debug_type($resolver)));
                }
                // continue to the next controller argument
                continue 2;
            }
            $representative = $controller;
            if (\is_array($representative)) {
                $representative = \sprintf('%s::%s()', \get_class($representative[0]), $representative[1]);
            } elseif (\is_object($representative)) {
                $representative = \get_class($representative);
            }
            throw new \RuntimeException(\sprintf('Controller "%s" requires that you provide a value for the "$%s" argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.', $representative, $metadata->getName()));
        }
        return $arguments;
    }
    /**
     * @return mixed[]
     */
    public static function getDefaultArgumentValueResolvers()
    {
        return [new \ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestAttributeValueResolver(), new \ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestValueResolver(), new \ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\SessionValueResolver(), new \ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver(), new \ECSPrefix20210507\Symfony\Component\HttpKernel\Controller\ArgumentResolver\VariadicValueResolver()];
    }
}