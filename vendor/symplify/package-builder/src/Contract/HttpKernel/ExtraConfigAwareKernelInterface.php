<?php

declare (strict_types=1);
namespace ECSPrefix20210923\Symplify\PackageBuilder\Contract\HttpKernel;

use ECSPrefix20210923\Symfony\Component\HttpKernel\KernelInterface;
use ECSPrefix20210923\Symplify\SmartFileSystem\SmartFileInfo;
interface ExtraConfigAwareKernelInterface extends \ECSPrefix20210923\Symfony\Component\HttpKernel\KernelInterface
{
    /**
     * @param string[]|SmartFileInfo[] $configs
     */
    public function setConfigs($configs) : void;
}
