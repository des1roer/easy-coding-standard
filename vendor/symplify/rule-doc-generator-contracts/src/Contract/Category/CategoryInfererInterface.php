<?php

declare (strict_types=1);
namespace ECSPrefix202210\Symplify\RuleDocGenerator\Contract\Category;

use ECSPrefix202210\Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
interface CategoryInfererInterface
{
    public function infer(RuleDefinition $ruleDefinition) : ?string;
}
