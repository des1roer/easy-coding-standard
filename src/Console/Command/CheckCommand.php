<?php

namespace Symplify\EasyCodingStandard\Console\Command;

use ECSPrefix20210515\Symfony\Component\Console\Input\InputInterface;
use ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface;
use Symplify\EasyCodingStandard\Reporter\ProcessedFileReporter;
use ECSPrefix20210515\Symplify\PackageBuilder\Console\ShellCode;
final class CheckCommand extends \Symplify\EasyCodingStandard\Console\Command\AbstractCheckCommand
{
    /**
     * @var ProcessedFileReporter
     */
    private $processedFileReporter;
    public function __construct(\Symplify\EasyCodingStandard\Reporter\ProcessedFileReporter $processedFileReporter)
    {
        $this->processedFileReporter = $processedFileReporter;
        parent::__construct();
    }
    /**
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Check coding standard in one or more directories.');
        parent::configure();
    }
    /**
     * @return int
     */
    protected function execute(\ECSPrefix20210515\Symfony\Component\Console\Input\InputInterface $input, \ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (!$this->loadedCheckersGuard->areSomeCheckerRegistered()) {
            $this->loadedCheckersGuard->report();
            return \ECSPrefix20210515\Symplify\PackageBuilder\Console\ShellCode::ERROR;
        }
        $this->configuration->resolveFromInput($input);
        // CLI paths override parameter paths
        if ($this->configuration->getSources() === []) {
            $this->configuration->setSources($this->configuration->getPaths());
        }
        $processedFilesCount = $this->easyCodingStandardApplication->run();
        return $this->processedFileReporter->report($processedFilesCount);
    }
}
