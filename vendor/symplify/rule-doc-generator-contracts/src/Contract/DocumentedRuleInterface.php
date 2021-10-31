<?php

declare (strict_types=1);
namespace ECSPrefix20211031\Symplify\RuleDocGenerator\Contract;

use ECSPrefix20211031\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @api
 */
interface DocumentedRuleInterface
{
    public function getRuleDefinition() : \ECSPrefix20211031\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
}
