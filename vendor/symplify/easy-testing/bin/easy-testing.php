<?php

declare (strict_types=1);
namespace ECSPrefix20211230;

use ECSPrefix20211230\Symplify\EasyTesting\Kernel\EasyTestingKernel;
use ECSPrefix20211230\Symplify\SymplifyKernel\ValueObject\KernelBootAndApplicationRun;
$possibleAutoloadPaths = [
    // dependency
    __DIR__ . '/../../../autoload.php',
    // after split package
    __DIR__ . '/../vendor/autoload.php',
    // monorepo
    __DIR__ . '/../../../vendor/autoload.php',
];
foreach ($possibleAutoloadPaths as $possibleAutoloadPath) {
    if (\file_exists($possibleAutoloadPath)) {
        require_once $possibleAutoloadPath;
        break;
    }
}
$kernelBootAndApplicationRun = new \ECSPrefix20211230\Symplify\SymplifyKernel\ValueObject\KernelBootAndApplicationRun(\ECSPrefix20211230\Symplify\EasyTesting\Kernel\EasyTestingKernel::class);
$kernelBootAndApplicationRun->run();
