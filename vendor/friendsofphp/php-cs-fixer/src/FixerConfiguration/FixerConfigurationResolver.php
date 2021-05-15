<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\FixerConfiguration;

use PhpCsFixer\Utils;
use ECSPrefix20210515\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use ECSPrefix20210515\Symfony\Component\OptionsResolver\OptionsResolver;
final class FixerConfigurationResolver implements \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
{
    /**
     * @var FixerOptionInterface[]
     */
    private $options = [];
    /**
     * @var string[]
     */
    private $registeredNames = [];
    /**
     * @param iterable<FixerOptionInterface> $options
     */
    public function __construct($options)
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
        if (empty($this->registeredNames)) {
            throw new \LogicException('Options cannot be empty.');
        }
    }
    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    public function getOptions()
    {
        return $this->options;
    }
    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    public function resolve(array $options)
    {
        $resolver = new \ECSPrefix20210515\Symfony\Component\OptionsResolver\OptionsResolver();
        foreach ($this->options as $option) {
            $name = $option->getName();
            if ($option instanceof \PhpCsFixer\FixerConfiguration\AliasedFixerOption) {
                $alias = $option->getAlias();
                if (\array_key_exists($alias, $options)) {
                    if (\array_key_exists($name, $options)) {
                        throw new \ECSPrefix20210515\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException(\sprintf('Aliased option "%s"/"%s" is passed multiple times.', $name, $alias));
                    }
                    \PhpCsFixer\Utils::triggerDeprecation(\sprintf('Option "%s" is deprecated, use "%s" instead.', $alias, $name));
                    $options[$name] = $options[$alias];
                    unset($options[$alias]);
                }
            }
            if ($option->hasDefault()) {
                $resolver->setDefault($name, $option->getDefault());
            } else {
                $resolver->setRequired($name);
            }
            $allowedValues = $option->getAllowedValues();
            if (null !== $allowedValues) {
                foreach ($allowedValues as &$allowedValue) {
                    if (\is_object($allowedValue) && \is_callable($allowedValue)) {
                        $allowedValue = static function ($values) use($allowedValue) {
                            return $allowedValue($values);
                        };
                    }
                }
                $resolver->setAllowedValues($name, $allowedValues);
            }
            $allowedTypes = $option->getAllowedTypes();
            if (null !== $allowedTypes) {
                $resolver->setAllowedTypes($name, $allowedTypes);
            }
            $normalizer = $option->getNormalizer();
            if (null !== $normalizer) {
                $resolver->setNormalizer($name, $normalizer);
            }
        }
        return $resolver->resolve($options);
    }
    /**
     * @throws \LogicException when the option is already defined
     *
     * @return $this
     */
    private function addOption(\PhpCsFixer\FixerConfiguration\FixerOptionInterface $option)
    {
        $name = $option->getName();
        if (\in_array($name, $this->registeredNames, \true)) {
            throw new \LogicException(\sprintf('The "%s" option is defined multiple times.', $name));
        }
        $this->options[] = $option;
        $this->registeredNames[] = $name;
        return $this;
    }
}
