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
namespace PhpCsFixer\Console\Output;

use PhpCsFixer\FixerFileProcessedEvent;
use ECSPrefix20210507\Symfony\Component\Console\Output\OutputInterface;
use ECSPrefix20210507\Symfony\Component\EventDispatcher\EventDispatcherInterface;
/**
 * Output writer to show the process of a FixCommand.
 *
 * @internal
 */
final class ProcessOutput implements \PhpCsFixer\Console\Output\ProcessOutputInterface
{
    /**
     * File statuses map.
     *
     * @var array
     */
    private static $eventStatusMap = [\PhpCsFixer\FixerFileProcessedEvent::STATUS_UNKNOWN => ['symbol' => '?', 'format' => '%s', 'description' => 'unknown'], \PhpCsFixer\FixerFileProcessedEvent::STATUS_INVALID => ['symbol' => 'I', 'format' => '<bg=red>%s</bg=red>', 'description' => 'invalid file syntax (file ignored)'], \PhpCsFixer\FixerFileProcessedEvent::STATUS_SKIPPED => ['symbol' => 'S', 'format' => '<fg=cyan>%s</fg=cyan>', 'description' => 'skipped (cached or empty file)'], \PhpCsFixer\FixerFileProcessedEvent::STATUS_NO_CHANGES => ['symbol' => '.', 'format' => '%s', 'description' => 'no changes'], \PhpCsFixer\FixerFileProcessedEvent::STATUS_FIXED => ['symbol' => 'F', 'format' => '<fg=green>%s</fg=green>', 'description' => 'fixed'], \PhpCsFixer\FixerFileProcessedEvent::STATUS_EXCEPTION => ['symbol' => 'E', 'format' => '<bg=red>%s</bg=red>', 'description' => 'error'], \PhpCsFixer\FixerFileProcessedEvent::STATUS_LINT => ['symbol' => 'E', 'format' => '<bg=red>%s</bg=red>', 'description' => 'error']];
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var int
     */
    private $files;
    /**
     * @var int
     */
    private $processedFiles = 0;
    /**
     * @var int
     */
    private $symbolsPerLine;
    /**
     * @param \ECSPrefix20210507\Symfony\Component\Console\Output\OutputInterface $output
     * @param \ECSPrefix20210507\Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     * @param int $width
     * @param int $nbFiles
     */
    public function __construct($output, $dispatcher, $width, $nbFiles)
    {
        $this->output = $output;
        $this->eventDispatcher = $dispatcher;
        $this->eventDispatcher->addListener(\PhpCsFixer\FixerFileProcessedEvent::NAME, [$this, 'onFixerFileProcessed']);
        $this->files = $nbFiles;
        //   max number of characters per line
        // - total length x 2 (e.g. "  1 / 123" => 6 digits and padding spaces)
        // - 11               (extra spaces, parentheses and percentage characters, e.g. " x / x (100%)")
        $this->symbolsPerLine = \max(1, $width - \strlen((string) $this->files) * 2 - 11);
    }
    public function __destruct()
    {
        $this->eventDispatcher->removeListener(\PhpCsFixer\FixerFileProcessedEvent::NAME, [$this, 'onFixerFileProcessed']);
    }
    /**
     * This class is not intended to be serialized,
     * and cannot be deserialized (see __wakeup method).
     * @return mixed[]
     */
    public function __sleep()
    {
        throw new \BadMethodCallException('Cannot serialize ' . __CLASS__);
    }
    /**
     * Disable the deserialization of the class to prevent attacker executing
     * code by leveraging the __destruct method.
     *
     * @see https://owasp.org/www-community/vulnerabilities/PHP_Object_Injection
     * @return void
     */
    public function __wakeup()
    {
        throw new \BadMethodCallException('Cannot unserialize ' . __CLASS__);
    }
    /**
     * @return void
     * @param \PhpCsFixer\FixerFileProcessedEvent $event
     */
    public function onFixerFileProcessed($event)
    {
        $status = self::$eventStatusMap[$event->getStatus()];
        $this->output->write($this->output->isDecorated() ? \sprintf($status['format'], $status['symbol']) : $status['symbol']);
        ++$this->processedFiles;
        $symbolsOnCurrentLine = $this->processedFiles % $this->symbolsPerLine;
        $isLast = $this->processedFiles === $this->files;
        if (0 === $symbolsOnCurrentLine || $isLast) {
            $this->output->write(\sprintf('%s %' . \strlen((string) $this->files) . 'd / %d (%3d%%)', $isLast && 0 !== $symbolsOnCurrentLine ? \str_repeat(' ', $this->symbolsPerLine - $symbolsOnCurrentLine) : '', $this->processedFiles, $this->files, \round($this->processedFiles / $this->files * 100)));
            if (!$isLast) {
                $this->output->writeln('');
            }
        }
    }
    /**
     * @return void
     */
    public function printLegend()
    {
        $symbols = [];
        foreach (self::$eventStatusMap as $status) {
            $symbol = $status['symbol'];
            if ('' === $symbol || isset($symbols[$symbol])) {
                continue;
            }
            $symbols[$symbol] = \sprintf('%s-%s', $this->output->isDecorated() ? \sprintf($status['format'], $symbol) : $symbol, $status['description']);
        }
        $this->output->write(\sprintf("\nLegend: %s\n", \implode(', ', $symbols)));
    }
}