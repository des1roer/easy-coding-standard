<?php

declare (strict_types=1);
namespace ECSPrefix20220610;

use PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer;
use PhpCsFixer\Fixer\ArrayNotation\WhitespaceAfterCommaInArrayFixer;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Basic\EncodingFixer;
use PhpCsFixer\Fixer\Casing\ConstantCaseFixer;
use PhpCsFixer\Fixer\Casing\LowercaseKeywordsFixer;
use PhpCsFixer\Fixer\CastNotation\LowercaseCastFixer;
use PhpCsFixer\Fixer\CastNotation\ShortScalarCastFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleClassElementPerStatementFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\Comment\NoTrailingWhitespaceInCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\ElseifFixer;
use PhpCsFixer\Fixer\ControlStructure\NoBreakCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\SwitchCaseSemicolonToColonFixer;
use PhpCsFixer\Fixer\ControlStructure\SwitchCaseSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\FunctionNotation\MethodArgumentSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\Import\NoLeadingImportSlashFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Import\SingleImportPerStatementFixer;
use PhpCsFixer\Fixer\Import\SingleLineAfterImportsFixer;
use PhpCsFixer\Fixer\LanguageConstruct\DeclareEqualNormalizeFixer;
use PhpCsFixer\Fixer\NamespaceNotation\BlankLineAfterNamespaceFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\NewWithBracesFixer;
use PhpCsFixer\Fixer\Operator\TernaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpTag\FullOpeningTagFixer;
use PhpCsFixer\Fixer\PhpTag\NoClosingTagFixer;
use PhpCsFixer\Fixer\Semicolon\NoSinglelineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Whitespace\IndentationTypeFixer;
use PhpCsFixer\Fixer\Whitespace\LineEndingFixer;
use PhpCsFixer\Fixer\Whitespace\NoSpacesInsideParenthesisFixer;
use PhpCsFixer\Fixer\Whitespace\NoTrailingWhitespaceFixer;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return static function (ECSConfig $ecsConfig) : void {
    $ecsConfig->ruleWithConfiguration(OrderedImportsFixer::class, ['imports_order' => ['class', 'function', 'const']]);
    $ecsConfig->ruleWithConfiguration(DeclareEqualNormalizeFixer::class, ['space' => 'none']);
    $ecsConfig->ruleWithConfiguration(BracesFixer::class, ['allow_single_line_closure' => \false, 'position_after_functions_and_oop_constructs' => 'next', 'position_after_control_structures' => 'same', 'position_after_anonymous_constructs' => 'same']);
    $ecsConfig->ruleWithConfiguration(VisibilityRequiredFixer::class, ['elements' => ['const', 'method', 'property']]);
    $ecsConfig->rules([BinaryOperatorSpacesFixer::class, BlankLineAfterNamespaceFixer::class, BlankLineAfterOpeningTagFixer::class, ClassDefinitionFixer::class, ConstantCaseFixer::class, ElseifFixer::class, EncodingFixer::class, FullOpeningTagFixer::class, FunctionDeclarationFixer::class, IndentationTypeFixer::class, LineEndingFixer::class, LowercaseCastFixer::class, LowercaseKeywordsFixer::class, NewWithBracesFixer::class, NoBlankLinesAfterClassOpeningFixer::class, NoBreakCommentFixer::class, NoClosingTagFixer::class, NoLeadingImportSlashFixer::class, NoSinglelineWhitespaceBeforeSemicolonsFixer::class, NoSpacesAfterFunctionNameFixer::class, NoSpacesInsideParenthesisFixer::class, NoTrailingWhitespaceFixer::class, NoTrailingWhitespaceInCommentFixer::class, NoWhitespaceBeforeCommaInArrayFixer::class, ReturnTypeDeclarationFixer::class, ShortScalarCastFixer::class, SingleBlankLineAtEofFixer::class, SingleImportPerStatementFixer::class, SingleLineAfterImportsFixer::class, SwitchCaseSemicolonToColonFixer::class, SwitchCaseSpaceFixer::class, TernaryOperatorSpacesFixer::class, UnaryOperatorSpacesFixer::class, VisibilityRequiredFixer::class, WhitespaceAfterCommaInArrayFixer::class]);
    $ecsConfig->ruleWithConfiguration(MethodArgumentSpaceFixer::class, ['on_multiline' => 'ensure_fully_multiline']);
    $ecsConfig->ruleWithConfiguration(SingleClassElementPerStatementFixer::class, ['elements' => ['property']]);
    $ecsConfig->ruleWithConfiguration(ConcatSpaceFixer::class, ['spacing' => 'one']);
    $ecsConfig->skip([SingleImportPerStatementFixer::class]);
};
