<?php

declare (strict_types=1);
namespace ECSPrefix20220609\Symplify\Skipper\Contract;

use ECSPrefix20220609\Symplify\SmartFileSystem\SmartFileInfo;
interface SkipVoterInterface
{
    /**
     * @param string|object $element
     */
    public function match($element) : bool;
    /**
     * @param string|object $element
     */
    public function shouldSkip($element, SmartFileInfo $smartFileInfo) : bool;
}
