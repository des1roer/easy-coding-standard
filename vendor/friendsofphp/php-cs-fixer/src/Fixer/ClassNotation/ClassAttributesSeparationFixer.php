<?php

declare (strict_types=1);
/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;
use ECSPrefix20210606\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
/**
 * Make sure there is one blank line above and below class elements.
 *
 * The exception is when an element is the first or last item in a 'classy'.
 *
 * @author SpacePossum
 */
final class ClassAttributesSeparationFixer extends \PhpCsFixer\AbstractFixer implements \PhpCsFixer\Fixer\ConfigurableFixerInterface, \PhpCsFixer\Fixer\WhitespacesAwareFixerInterface
{
    /**
     * @internal
     */
    const SPACING_NONE = 'none';
    /**
     * @internal
     */
    const SPACING_ONE = 'one';
    const SUPPORTED_SPACINGS = [self::SPACING_NONE, self::SPACING_ONE];
    const SUPPORTED_TYPES = ['const', 'method', 'property'];
    /**
     * @var array<string, string>
     */
    private $classElementTypes = [];
    /**
     * {@inheritdoc}
     * @return void
     */
    public function configure(array $configuration)
    {
        parent::configure($configuration);
        $this->classElementTypes = [];
        // reset previous configuration
        foreach ($this->configuration['elements'] as $elementType => $spacing) {
            $this->classElementTypes[$elementType] = $spacing;
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getDefinition() : \PhpCsFixer\FixerDefinition\FixerDefinitionInterface
    {
        return new \PhpCsFixer\FixerDefinition\FixerDefinition('Class, trait and interface elements must be separated with one or none blank line.', [new \PhpCsFixer\FixerDefinition\CodeSample('<?php
final class Sample
{
    protected function foo()
    {
    }
    protected function bar()
    {
    }


}
'), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
class Sample
{private $a; // a is awesome
    /** second in a hour */
    private $b;
}
', ['elements' => ['property' => self::SPACING_ONE]]), new \PhpCsFixer\FixerDefinition\CodeSample('<?php
class Sample
{
    const A = 1;
    /** seconds in some hours */
    const B = 3600;
}
', ['elements' => ['const' => self::SPACING_ONE]])]);
    }
    /**
     * {@inheritdoc}
     *
     * Must run before BracesFixer, IndentationTypeFixer.
     * Must run after OrderedClassElementsFixer, SingleClassElementPerStatementFixer.
     */
    public function getPriority() : int
    {
        return 55;
    }
    /**
     * {@inheritdoc}
     */
    public function isCandidate(\PhpCsFixer\Tokenizer\Tokens $tokens) : bool
    {
        return $tokens->isAnyTokenKindsFound(\PhpCsFixer\Tokenizer\Token::getClassyTokenKinds());
    }
    /**
     * {@inheritdoc}
     * @return void
     */
    protected function applyFix(\SplFileInfo $file, \PhpCsFixer\Tokenizer\Tokens $tokens)
    {
        $tokensAnalyzer = new \PhpCsFixer\Tokenizer\TokensAnalyzer($tokens);
        $class = $classStart = $classEnd = \false;
        foreach (\array_reverse($tokensAnalyzer->getClassyElements(), \true) as $index => $element) {
            if (!isset($this->classElementTypes[$element['type']])) {
                continue;
                // not configured to be fixed
            }
            $spacing = $this->classElementTypes[$element['type']];
            if ($element['classIndex'] !== $class) {
                $class = $element['classIndex'];
                $classStart = $tokens->getNextTokenOfKind($class, ['{']);
                $classEnd = $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_CURLY_BRACE, $classStart);
            }
            if ('method' === $element['type'] && !$tokens[$class]->isGivenKind(\T_INTERFACE)) {
                // method of class or trait
                $attributes = $tokensAnalyzer->getMethodAttributes($index);
                $methodEnd = \true === $attributes['abstract'] ? $tokens->getNextTokenOfKind($index, [';']) : $tokens->findBlockEnd(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_CURLY_BRACE, $tokens->getNextTokenOfKind($index, ['{']));
                $this->fixSpaceBelowClassMethod($tokens, $classEnd, $methodEnd, $spacing);
                $this->fixSpaceAboveClassElement($tokens, $classStart, $index, $spacing);
                continue;
            }
            // `const`, `property` or `method` of an `interface`
            $this->fixSpaceBelowClassElement($tokens, $classEnd, $tokens->getNextTokenOfKind($index, [';']), $spacing);
            $this->fixSpaceAboveClassElement($tokens, $classStart, $index, $spacing);
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition() : \PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface
    {
        return new \PhpCsFixer\FixerConfiguration\FixerConfigurationResolver([(new \PhpCsFixer\FixerConfiguration\FixerOptionBuilder('elements', 'Dictionary of `const|method|property` => `none|one` values.'))->setAllowedTypes(['array'])->setAllowedValues([static function (array $option) {
            foreach ($option as $type => $spacing) {
                if (!\in_array($type, self::SUPPORTED_TYPES, \true)) {
                    throw new \ECSPrefix20210606\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException(\sprintf('Unexpected element type, expected any of "%s", got "%s".', \implode('", "', self::SUPPORTED_TYPES), \gettype($type) . '#' . $type));
                }
                if (!\in_array($spacing, self::SUPPORTED_SPACINGS, \true)) {
                    throw new \ECSPrefix20210606\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException(\sprintf('Unexpected spacing for element type "%s", expected any of "%s", got "%s".', $spacing, \implode('", "', self::SUPPORTED_SPACINGS), \is_object($spacing) ? \get_class($spacing) : (null === $spacing ? 'null' : \gettype($spacing) . '#' . $spacing)));
                }
            }
            return \true;
        }])->setDefault(['const' => self::SPACING_ONE, 'method' => self::SPACING_ONE, 'property' => self::SPACING_ONE])->getOption()]);
    }
    /**
     * Fix spacing below an element of a class, interface or trait.
     *
     * Deals with comments, PHPDocs and spaces above the element with respect to the position of the
     * element within the class, interface or trait.
     * @return void
     */
    private function fixSpaceBelowClassElement(\PhpCsFixer\Tokenizer\Tokens $tokens, int $classEndIndex, int $elementEndIndex, string $spacing)
    {
        for ($nextNotWhite = $elementEndIndex + 1;; ++$nextNotWhite) {
            if (($tokens[$nextNotWhite]->isComment() || $tokens[$nextNotWhite]->isWhitespace()) && \false === \strpos($tokens[$nextNotWhite]->getContent(), "\n")) {
                continue;
            }
            break;
        }
        if ($tokens[$nextNotWhite]->isWhitespace()) {
            $nextNotWhite = $tokens->getNextNonWhitespace($nextNotWhite);
        }
        $functionIndex = $tokens->getTokenNotOfKindsSibling($nextNotWhite - 1, 1, [\T_ABSTRACT, \T_FINAL, \T_PUBLIC, \T_PROTECTED, \T_PRIVATE, \T_STATIC, \T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT]);
        if ($tokens[$functionIndex]->isGivenKind(\T_FUNCTION)) {
            $this->correctLineBreaks($tokens, $elementEndIndex, $nextNotWhite, 2);
            return;
        }
        $this->correctLineBreaks($tokens, $elementEndIndex, $nextNotWhite, $nextNotWhite === $classEndIndex || self::SPACING_NONE === $spacing ? 1 : 2);
    }
    /**
     * Fix spacing below a method of a class or trait.
     *
     * Deals with comments, PHPDocs and spaces above the method with respect to the position of the
     * method within the class or trait.
     * @return void
     */
    private function fixSpaceBelowClassMethod(\PhpCsFixer\Tokenizer\Tokens $tokens, int $classEndIndex, int $elementEndIndex, string $spacing)
    {
        $nextNotWhite = $tokens->getNextNonWhitespace($elementEndIndex);
        $this->correctLineBreaks($tokens, $elementEndIndex, $nextNotWhite, $nextNotWhite === $classEndIndex || self::SPACING_NONE === $spacing ? 1 : 2);
    }
    /**
     * Fix spacing above an element of a class, interface or trait.
     *
     * Deals with comments, PHPDocs and spaces above the element with respect to the position of the
     * element within the class, interface or trait.
     *
     * @param int $classStartIndex index of the class Token the element is in
     * @param int $elementIndex    index of the element to fix
     * @return void
     */
    private function fixSpaceAboveClassElement(\PhpCsFixer\Tokenizer\Tokens $tokens, int $classStartIndex, int $elementIndex, string $spacing)
    {
        static $methodAttr = [\T_PRIVATE, \T_PROTECTED, \T_PUBLIC, \T_ABSTRACT, \T_FINAL, \T_STATIC, \T_STRING, \T_NS_SEPARATOR, \T_VAR, \PhpCsFixer\Tokenizer\CT::T_NULLABLE_TYPE, \PhpCsFixer\Tokenizer\CT::T_ARRAY_TYPEHINT, \PhpCsFixer\Tokenizer\CT::T_TYPE_ALTERNATION];
        $nonWhiteAbove = null;
        // find out where the element definition starts
        $firstElementAttributeIndex = $elementIndex;
        for ($i = $elementIndex; $i > $classStartIndex; --$i) {
            $nonWhiteAbove = $tokens->getNonWhitespaceSibling($i, -1);
            if (null !== $nonWhiteAbove && $tokens[$nonWhiteAbove]->isGivenKind($methodAttr)) {
                $firstElementAttributeIndex = $nonWhiteAbove;
            } else {
                break;
            }
        }
        // deal with comments above a element
        if ($tokens[$nonWhiteAbove]->isGivenKind(\T_COMMENT)) {
            if (1 === $firstElementAttributeIndex - $nonWhiteAbove) {
                // no white space found between comment and element start
                $this->correctLineBreaks($tokens, $nonWhiteAbove, $firstElementAttributeIndex, 1);
                return;
            }
            // $tokens[$nonWhiteAbove+1] is always a white space token here
            if (\substr_count($tokens[$nonWhiteAbove + 1]->getContent(), "\n") > 1) {
                // more than one line break, always bring it back to 2 line breaks between the element start and what is above it
                $this->correctLineBreaks($tokens, $nonWhiteAbove, $firstElementAttributeIndex, 2);
                return;
            }
            // there are 2 cases:
            if ($tokens[$nonWhiteAbove - 1]->isWhitespace() && \substr_count($tokens[$nonWhiteAbove - 1]->getContent(), "\n") > 0) {
                // 1. The comment is meant for the element (although not a PHPDoc),
                //    make sure there is one line break between the element and the comment...
                $this->correctLineBreaks($tokens, $nonWhiteAbove, $firstElementAttributeIndex, 1);
                //    ... and make sure there is blank line above the comment (with the exception when it is directly after a class opening)
                $nonWhiteAbove = $this->findCommentBlockStart($tokens, $nonWhiteAbove);
                $nonWhiteAboveComment = $tokens->getNonWhitespaceSibling($nonWhiteAbove, -1);
                $this->correctLineBreaks($tokens, $nonWhiteAboveComment, $nonWhiteAbove, $nonWhiteAboveComment === $classStartIndex ? 1 : 2);
            } else {
                // 2. The comment belongs to the code above the element,
                //    make sure there is a blank line above the element (i.e. 2 line breaks)
                $this->correctLineBreaks($tokens, $nonWhiteAbove, $firstElementAttributeIndex, 2);
            }
            return;
        }
        // deal with element with a PHPDoc above it
        if ($tokens[$nonWhiteAbove]->isGivenKind(\T_DOC_COMMENT)) {
            // there should be one linebreak between the element and the PHPDoc above it
            $this->correctLineBreaks($tokens, $nonWhiteAbove, $firstElementAttributeIndex, 1);
            // there should be one blank line between the PHPDoc and whatever is above (with the exception when it is directly after a class opening)
            $nonWhiteAbovePHPDoc = $tokens->getNonWhitespaceSibling($nonWhiteAbove, -1);
            $this->correctLineBreaks($tokens, $nonWhiteAbovePHPDoc, $nonWhiteAbove, $nonWhiteAbovePHPDoc === $classStartIndex ? 1 : 2);
            return;
        }
        // deal with element with an attribute above it
        if ($tokens[$nonWhiteAbove]->isGivenKind(\PhpCsFixer\Tokenizer\CT::T_ATTRIBUTE_CLOSE)) {
            // there should be one linebreak between the element and the attribute above it
            $this->correctLineBreaks($tokens, $nonWhiteAbove, $firstElementAttributeIndex, 1);
            // make sure there is blank line above the comment (with the exception when it is directly after a class opening)
            $nonWhiteAbove = $this->findAttributeBlockStart($tokens, $nonWhiteAbove);
            $nonWhiteAboveComment = $tokens->getNonWhitespaceSibling($nonWhiteAbove, -1);
            $this->correctLineBreaks($tokens, $nonWhiteAboveComment, $nonWhiteAbove, $nonWhiteAboveComment === $classStartIndex ? 1 : 2);
            return;
        }
        $this->correctLineBreaks($tokens, $nonWhiteAbove, $firstElementAttributeIndex, $nonWhiteAbove === $classStartIndex || self::SPACING_NONE === $spacing ? 1 : 2);
    }
    /**
     * @return void
     */
    private function correctLineBreaks(\PhpCsFixer\Tokenizer\Tokens $tokens, int $startIndex, int $endIndex, int $reqLineCount = 2)
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        ++$startIndex;
        $numbOfWhiteTokens = $endIndex - $startIndex;
        if (0 === $numbOfWhiteTokens) {
            $tokens->insertAt($startIndex, new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, \str_repeat($lineEnding, $reqLineCount)]));
            return;
        }
        $lineBreakCount = $this->getLineBreakCount($tokens, $startIndex, $endIndex);
        if ($reqLineCount === $lineBreakCount) {
            return;
        }
        if ($lineBreakCount < $reqLineCount) {
            $tokens[$startIndex] = new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, \str_repeat($lineEnding, $reqLineCount - $lineBreakCount) . $tokens[$startIndex]->getContent()]);
            return;
        }
        // $lineCount = > $reqLineCount : check the one Token case first since this one will be true most of the time
        if (1 === $numbOfWhiteTokens) {
            $tokens[$startIndex] = new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, \PhpCsFixer\Preg::replace('/\\r\\n|\\n/', '', $tokens[$startIndex]->getContent(), $lineBreakCount - $reqLineCount)]);
            return;
        }
        // $numbOfWhiteTokens = > 1
        $toReplaceCount = $lineBreakCount - $reqLineCount;
        for ($i = $startIndex; $i < $endIndex && $toReplaceCount > 0; ++$i) {
            $tokenLineCount = \substr_count($tokens[$i]->getContent(), "\n");
            if ($tokenLineCount > 0) {
                $tokens[$i] = new \PhpCsFixer\Tokenizer\Token([\T_WHITESPACE, \PhpCsFixer\Preg::replace('/\\r\\n|\\n/', '', $tokens[$i]->getContent(), \min($toReplaceCount, $tokenLineCount))]);
                $toReplaceCount -= $tokenLineCount;
            }
        }
    }
    private function getLineBreakCount(\PhpCsFixer\Tokenizer\Tokens $tokens, int $whiteSpaceStartIndex, int $whiteSpaceEndIndex) : int
    {
        $lineCount = 0;
        for ($i = $whiteSpaceStartIndex; $i < $whiteSpaceEndIndex; ++$i) {
            $lineCount += \substr_count($tokens[$i]->getContent(), "\n");
        }
        return $lineCount;
    }
    private function findCommentBlockStart(\PhpCsFixer\Tokenizer\Tokens $tokens, int $commentIndex) : int
    {
        $start = $commentIndex;
        for ($i = $commentIndex - 1; $i > 0; --$i) {
            if ($tokens[$i]->isComment()) {
                $start = $i;
                continue;
            }
            if (!$tokens[$i]->isWhitespace() || $this->getLineBreakCount($tokens, $i, $i + 1) > 1) {
                break;
            }
        }
        return $start;
    }
    /**
     * @param int $index attribute close index
     */
    private function findAttributeBlockStart(\PhpCsFixer\Tokenizer\Tokens $tokens, int $index) : int
    {
        $start = $index = $tokens->findBlockStart(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_ATTRIBUTE, $index);
        for ($i = $index - 1; $i > 0; --$i) {
            if ($tokens[$i]->isGivenKind(\PhpCsFixer\Tokenizer\CT::T_ATTRIBUTE_CLOSE)) {
                $start = $i = $tokens->findBlockStart(\PhpCsFixer\Tokenizer\Tokens::BLOCK_TYPE_ATTRIBUTE, $i);
                continue;
            }
            if (!$tokens[$i]->isWhitespace() || $this->getLineBreakCount($tokens, $i, $i + 1) > 1) {
                break;
            }
        }
        return $start;
    }
}
