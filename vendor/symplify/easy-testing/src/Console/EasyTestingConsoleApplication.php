<?php

declare (strict_types=1);
namespace ECSPrefix20210521\Symplify\EasyTesting\Console;

use ECSPrefix20210521\Symfony\Component\Console\Application;
use ECSPrefix20210521\Symfony\Component\Console\Command\Command;
use ECSPrefix20210521\Symplify\PackageBuilder\Console\Command\CommandNaming;
final class EasyTestingConsoleApplication extends \ECSPrefix20210521\Symfony\Component\Console\Application
{
    /**
     * @param Command[] $commands
     */
    public function __construct(\ECSPrefix20210521\Symplify\PackageBuilder\Console\Command\CommandNaming $commandNaming, array $commands)
    {
        foreach ($commands as $command) {
            $commandName = $commandNaming->resolveFromCommand($command);
            $command->setName($commandName);
            $this->add($command);
        }
        parent::__construct('Easy Testing');
    }
}