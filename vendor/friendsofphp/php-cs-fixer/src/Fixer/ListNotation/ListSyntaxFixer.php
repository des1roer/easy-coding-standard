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
namespace PhpCsFixer\Fixer\ListNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
/**
 * @author SpacePossum
 */
final class ListSyntaxFixer extends \PhpCsFixer\AbstractFixer implements \PhpCsFixer\Fixer\ConfigurableFixerInterface
{
    private $candidateTokenKind;
    /**
     * Use 'syntax' => 'long'|'short'.
     *
     * @param array<string, string> $configuration
     *
     * @throws InvalidFixerConfigurationException
     * @return void
     */
    public function configure($configuration)
    {
        parent::configure($configuration);
        $this->candidateTokenKind = 'long' === $this->configuration['syntax'] ? \PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN : \T_LIST;
    }
    /**
     * {@inheritdoc}
     * @return \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
     */
    public function getDefinition()
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('List (`array` destructuring) assignment should be declared using the configured syntax. Requires PHP >= 7.1.', [new \PhpCsFixer\FixerDefinition\VersionSpecificCodeSample("<?php\nlist(\$sample) = \$array;\n", new \PhpCsFixer\FixerDefinition\VersionSpecification(70100)), new \PhpCsFixer\FixerDefinition\VersionSpecificCodeSample("<?php\n[\$sample] = \$array;\n", new \PhpCsFixer\FixerDefinition\VersionSpecification(70100), ['syntax' => 'long'])]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run before BinaryOperatorSpacesFixer, TernaryOperatorSpacesFixer.
     * @return int
     */
    public function getPriority()
    {
        return 1;
    }
    /**
     * {@inheritdoc}
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @return bool
     */
    public function isCandidate($tokens)
    {
        return \PHP_VERSION_ID >= 70100 && $tokens->isTokenKindFound($this->candidateTokenKind);
    }
    /**
     * {@inheritdoc}
     * @return void
     * @param \SplFileInfo $file
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     */
    protected function applyFix($file, $tokens)
    {
        for ($index = $tokens->count() - 1; 0 <= $index; --$index) {
            if ($tokens[$index]->isGivenKind($this->candidateTokenKind)) {
                if (\T_LIST === $this->candidateTokenKind) {
                    $this->fixToShortSyntax($tokens, $index);
                } else {
                    $this->fixToLongSyntax($tokens, $index);
                }
            }
        }
    }
    /**
     * {@inheritdoc}
     * @return \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
     */
    protected function createConfigurationDefinition()
    {
        return new \PhpCsFixer\FixerConfiguration\FixerConfigurationResolver([(new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('syntax', 'Whether to use the `long` or `short` `list` syntax.'))->setAllowedValues(['long', 'short'])->setDefault('short')->getOption()]);
    }
    /**
     * @return void
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param int $index
     */
    private function fixToLongSyntax($tokens, $index)
    {
        static $typesOfInterest = [[\PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE], '['];
        $closeIndex = $tokens->getNextTokenOfKind($index, $typesOfInterest);
        if (!$tokens[$closeIndex]->isGivenKind(\PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE)) {
            return;
        }
        $tokens[$index] = new \PhpCsFixer\Tokenizer\Token('(');
        $tokens[$closeIndex] = new \PhpCsFixer\Tokenizer\Token(')');
        $tokens->insertAt($index, new \PhpCsFixer\Tokenizer\Token([\T_LIST, 'list']));
    }
    /**
     * @return void
     * @param \PhpCsFixer\Tokenizer\Tokens $tokens
     * @param int $index
     */
    private function fixToShortSyntax($tokens, $index)
    {
        $openIndex = $tokens->getNextTokenOfKind($index, ['(']);
        $closeIndex = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openIndex);
        $tokens[$openIndex] = new \PhpCsFixer\Tokenizer\Token([\PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_OPEN, '[']);
        $tokens[$closeIndex] = new \PhpCsFixer\Tokenizer\Token([\PhpCsFixer\Tokenizer\CT::T_DESTRUCTURING_SQUARE_BRACE_CLOSE, ']']);
        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }
}