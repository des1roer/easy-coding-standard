<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ECSPrefix20210515\Symfony\Component\Console\Helper;

use ECSPrefix20210515\Symfony\Component\Console\Cursor;
use ECSPrefix20210515\Symfony\Component\Console\Exception\LogicException;
use ECSPrefix20210515\Symfony\Component\Console\Output\ConsoleOutputInterface;
use ECSPrefix20210515\Symfony\Component\Console\Output\ConsoleSectionOutput;
use ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface;
use ECSPrefix20210515\Symfony\Component\Console\Terminal;
/**
 * The ProgressBar provides helpers to display progress output.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Chris Jones <leeked@gmail.com>
 */
final class ProgressBar
{
    private $barWidth = 28;
    private $barChar;
    private $emptyBarChar = '-';
    private $progressChar = '>';
    private $format;
    private $internalFormat;
    private $redrawFreq = 1;
    private $writeCount;
    private $lastWriteTime;
    private $minSecondsBetweenRedraws = 0;
    private $maxSecondsBetweenRedraws = 1;
    private $output;
    private $step = 0;
    private $max;
    private $startTime;
    private $stepWidth;
    private $percent = 0.0;
    private $formatLineCount;
    private $messages = [];
    private $overwrite = \true;
    private $terminal;
    private $previousMessage;
    private $cursor;
    private static $formatters;
    private static $formats;
    /**
     * @param int $max Maximum steps (0 if unknown)
     * @param float $minSecondsBetweenRedraws
     */
    public function __construct(\ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface $output, $max = 0, $minSecondsBetweenRedraws = 1 / 25)
    {
        $max = (int) $max;
        $minSecondsBetweenRedraws = (double) $minSecondsBetweenRedraws;
        if ($output instanceof \ECSPrefix20210515\Symfony\Component\Console\Output\ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }
        $this->output = $output;
        $this->setMaxSteps($max);
        $this->terminal = new \ECSPrefix20210515\Symfony\Component\Console\Terminal();
        if (0 < $minSecondsBetweenRedraws) {
            $this->redrawFreq = null;
            $this->minSecondsBetweenRedraws = $minSecondsBetweenRedraws;
        }
        if (!$this->output->isDecorated()) {
            // disable overwrite when output does not support ANSI codes.
            $this->overwrite = \false;
            // set a reasonable redraw frequency so output isn't flooded
            $this->redrawFreq = null;
        }
        $this->startTime = \time();
        $this->cursor = new \ECSPrefix20210515\Symfony\Component\Console\Cursor($output);
    }
    /**
     * Sets a placeholder formatter for a given name.
     *
     * This method also allow you to override an existing placeholder.
     *
     * @param string   $name     The placeholder name (including the delimiter char like %)
     * @param callable $callable A PHP callable
     * @return void
     */
    public static function setPlaceholderFormatterDefinition($name, callable $callable)
    {
        $name = (string) $name;
        if (!self::$formatters) {
            self::$formatters = self::initPlaceholderFormatters();
        }
        self::$formatters[$name] = $callable;
    }
    /**
     * Gets the placeholder formatter for a given name.
     *
     * @param string $name The placeholder name (including the delimiter char like %)
     *
     * @return callable|null A PHP callable
     */
    public static function getPlaceholderFormatterDefinition($name)
    {
        $name = (string) $name;
        if (!self::$formatters) {
            self::$formatters = self::initPlaceholderFormatters();
        }
        return isset(self::$formatters[$name]) ? self::$formatters[$name] : null;
    }
    /**
     * Sets a format for a given name.
     *
     * This method also allow you to override an existing format.
     *
     * @param string $name   The format name
     * @param string $format A format string
     * @return void
     */
    public static function setFormatDefinition($name, $format)
    {
        $name = (string) $name;
        $format = (string) $format;
        if (!self::$formats) {
            self::$formats = self::initFormats();
        }
        self::$formats[$name] = $format;
    }
    /**
     * Gets the format for a given name.
     *
     * @param string $name The format name
     *
     * @return string|null A format string
     */
    public static function getFormatDefinition($name)
    {
        $name = (string) $name;
        if (!self::$formats) {
            self::$formats = self::initFormats();
        }
        return isset(self::$formats[$name]) ? self::$formats[$name] : null;
    }
    /**
     * Associates a text with a named placeholder.
     *
     * The text is displayed when the progress bar is rendered but only
     * when the corresponding placeholder is part of the custom format line
     * (by wrapping the name with %).
     *
     * @param string $message The text to associate with the placeholder
     * @param string $name    The name of the placeholder
     */
    public function setMessage($message, $name = 'message')
    {
        $message = (string) $message;
        $name = (string) $name;
        $this->messages[$name] = $message;
    }
    /**
     * @param string $name
     */
    public function getMessage($name = 'message')
    {
        $name = (string) $name;
        return $this->messages[$name];
    }
    /**
     * @return int
     */
    public function getStartTime()
    {
        return $this->startTime;
    }
    /**
     * @return int
     */
    public function getMaxSteps()
    {
        return $this->max;
    }
    /**
     * @return int
     */
    public function getProgress()
    {
        return $this->step;
    }
    /**
     * @return int
     */
    private function getStepWidth()
    {
        return $this->stepWidth;
    }
    /**
     * @return float
     */
    public function getProgressPercent()
    {
        return $this->percent;
    }
    /**
     * @return float
     */
    public function getBarOffset()
    {
        return \floor($this->max ? $this->percent * $this->barWidth : (null === $this->redrawFreq ? \min(5, $this->barWidth / 15) * $this->writeCount : $this->step) % $this->barWidth);
    }
    /**
     * @return float
     */
    public function getEstimated()
    {
        if (!$this->step) {
            return 0;
        }
        return \round((\time() - $this->startTime) / $this->step * $this->max);
    }
    /**
     * @return float
     */
    public function getRemaining()
    {
        if (!$this->step) {
            return 0;
        }
        return \round((\time() - $this->startTime) / $this->step * ($this->max - $this->step));
    }
    /**
     * @param int $size
     */
    public function setBarWidth($size)
    {
        $size = (int) $size;
        $this->barWidth = \max(1, $size);
    }
    /**
     * @return int
     */
    public function getBarWidth()
    {
        return $this->barWidth;
    }
    /**
     * @param string $char
     */
    public function setBarCharacter($char)
    {
        $char = (string) $char;
        $this->barChar = $char;
    }
    /**
     * @return string
     */
    public function getBarCharacter()
    {
        if (null === $this->barChar) {
            return $this->max ? '=' : $this->emptyBarChar;
        }
        return $this->barChar;
    }
    /**
     * @param string $char
     */
    public function setEmptyBarCharacter($char)
    {
        $char = (string) $char;
        $this->emptyBarChar = $char;
    }
    /**
     * @return string
     */
    public function getEmptyBarCharacter()
    {
        return $this->emptyBarChar;
    }
    /**
     * @param string $char
     */
    public function setProgressCharacter($char)
    {
        $char = (string) $char;
        $this->progressChar = $char;
    }
    /**
     * @return string
     */
    public function getProgressCharacter()
    {
        return $this->progressChar;
    }
    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $format = (string) $format;
        $this->format = null;
        $this->internalFormat = $format;
    }
    /**
     * Sets the redraw frequency.
     *
     * @param int|null $freq The frequency in steps
     */
    public function setRedrawFrequency($freq)
    {
        $this->redrawFreq = null !== $freq ? \max(1, $freq) : null;
    }
    /**
     * @return void
     * @param float $seconds
     */
    public function minSecondsBetweenRedraws($seconds)
    {
        $seconds = (double) $seconds;
        $this->minSecondsBetweenRedraws = $seconds;
    }
    /**
     * @return void
     * @param float $seconds
     */
    public function maxSecondsBetweenRedraws($seconds)
    {
        $seconds = (double) $seconds;
        $this->maxSecondsBetweenRedraws = $seconds;
    }
    /**
     * Returns an iterator that will automatically update the progress bar when iterated.
     *
     * @param int $max Number of steps to complete the bar (0 if indeterminate), if null it will be inferred from $iterable
     * @param mixed[] $iterable
     * @return mixed[]
     */
    public function iterate($iterable, $max = null)
    {
        $max = (int) $max;
        $this->start(isset($max) ? $max : (\is_countable($iterable) ? \count($iterable) : 0));
        foreach ($iterable as $key => $value) {
            (yield $key => $value);
            $this->advance();
        }
        $this->finish();
    }
    /**
     * Starts the progress output.
     *
     * @param int $max Number of steps to complete the bar (0 if indeterminate), null to leave unchanged
     */
    public function start($max = null)
    {
        $this->startTime = \time();
        $this->step = 0;
        $this->percent = 0.0;
        if (null !== $max) {
            $this->setMaxSteps($max);
        }
        $this->display();
    }
    /**
     * Advances the progress output X steps.
     *
     * @param int $step Number of steps to advance
     */
    public function advance($step = 1)
    {
        $step = (int) $step;
        $this->setProgress($this->step + $step);
    }
    /**
     * Sets whether to overwrite the progressbar, false for new line.
     * @param bool $overwrite
     */
    public function setOverwrite($overwrite)
    {
        $overwrite = (bool) $overwrite;
        $this->overwrite = $overwrite;
    }
    /**
     * @param int $step
     */
    public function setProgress($step)
    {
        $step = (int) $step;
        if ($this->max && $step > $this->max) {
            $this->max = $step;
        } elseif ($step < 0) {
            $step = 0;
        }
        $redrawFreq = $this->redrawFreq !== null ? $this->redrawFreq : ($this->max ?: 10) / 10;
        $prevPeriod = (int) ($this->step / $redrawFreq);
        $currPeriod = (int) ($step / $redrawFreq);
        $this->step = $step;
        $this->percent = $this->max ? (float) $this->step / $this->max : 0;
        $timeInterval = \microtime(\true) - $this->lastWriteTime;
        // Draw regardless of other limits
        if ($this->max === $step) {
            $this->display();
            return;
        }
        // Throttling
        if ($timeInterval < $this->minSecondsBetweenRedraws) {
            return;
        }
        // Draw each step period, but not too late
        if ($prevPeriod !== $currPeriod || $timeInterval >= $this->maxSecondsBetweenRedraws) {
            $this->display();
        }
    }
    /**
     * @param int $max
     */
    public function setMaxSteps($max)
    {
        $max = (int) $max;
        $this->format = null;
        $this->max = \max(0, $max);
        $this->stepWidth = $this->max ? \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::strlen((string) $this->max) : 4;
    }
    /**
     * Finishes the progress output.
     * @return void
     */
    public function finish()
    {
        if (!$this->max) {
            $this->max = $this->step;
        }
        if ($this->step === $this->max && !$this->overwrite) {
            // prevent double 100% output
            return;
        }
        $this->setProgress($this->max);
    }
    /**
     * Outputs the current progress string.
     * @return void
     */
    public function display()
    {
        if (\ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_QUIET === $this->output->getVerbosity()) {
            return;
        }
        if (null === $this->format) {
            $this->setRealFormat($this->internalFormat ?: $this->determineBestFormat());
        }
        $this->overwrite($this->buildLine());
    }
    /**
     * Removes the progress bar from the current line.
     *
     * This is useful if you wish to write some output
     * while a progress bar is running.
     * Call display() to show the progress bar again.
     * @return void
     */
    public function clear()
    {
        if (!$this->overwrite) {
            return;
        }
        if (null === $this->format) {
            $this->setRealFormat($this->internalFormat ?: $this->determineBestFormat());
        }
        $this->overwrite('');
    }
    /**
     * @param string $format
     */
    private function setRealFormat($format)
    {
        $format = (string) $format;
        // try to use the _nomax variant if available
        if (!$this->max && null !== self::getFormatDefinition($format . '_nomax')) {
            $this->format = self::getFormatDefinition($format . '_nomax');
        } elseif (null !== self::getFormatDefinition($format)) {
            $this->format = self::getFormatDefinition($format);
        } else {
            $this->format = $format;
        }
        $this->formatLineCount = \substr_count($this->format, "\n");
    }
    /**
     * Overwrites a previous message to the output.
     * @return void
     * @param string $message
     */
    private function overwrite($message)
    {
        $message = (string) $message;
        if ($this->previousMessage === $message) {
            return;
        }
        $originalMessage = $message;
        if ($this->overwrite) {
            if (null !== $this->previousMessage) {
                if ($this->output instanceof \ECSPrefix20210515\Symfony\Component\Console\Output\ConsoleSectionOutput) {
                    $messageLines = \explode("\n", $message);
                    $lineCount = \count($messageLines);
                    foreach ($messageLines as $messageLine) {
                        $messageLineLength = \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::strlenWithoutDecoration($this->output->getFormatter(), $messageLine);
                        if ($messageLineLength > $this->terminal->getWidth()) {
                            $lineCount += \floor($messageLineLength / $this->terminal->getWidth());
                        }
                    }
                    $this->output->clear($lineCount);
                } else {
                    if ($this->formatLineCount > 0) {
                        $this->cursor->moveUp($this->formatLineCount);
                    }
                    $this->cursor->moveToColumn(1);
                    $this->cursor->clearLine();
                }
            }
        } elseif ($this->step > 0) {
            $message = \PHP_EOL . $message;
        }
        $this->previousMessage = $originalMessage;
        $this->lastWriteTime = \microtime(\true);
        $this->output->write($message);
        ++$this->writeCount;
    }
    /**
     * @return string
     */
    private function determineBestFormat()
    {
        switch ($this->output->getVerbosity()) {
            // OutputInterface::VERBOSITY_QUIET: display is disabled anyway
            case \ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERBOSE:
                return $this->max ? 'verbose' : 'verbose_nomax';
            case \ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_VERY_VERBOSE:
                return $this->max ? 'very_verbose' : 'very_verbose_nomax';
            case \ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG:
                return $this->max ? 'debug' : 'debug_nomax';
            default:
                return $this->max ? 'normal' : 'normal_nomax';
        }
    }
    /**
     * @return mixed[]
     */
    private static function initPlaceholderFormatters()
    {
        return ['bar' => function (self $bar, \ECSPrefix20210515\Symfony\Component\Console\Output\OutputInterface $output) {
            $completeBars = $bar->getBarOffset();
            $display = \str_repeat($bar->getBarCharacter(), $completeBars);
            if ($completeBars < $bar->getBarWidth()) {
                $emptyBars = $bar->getBarWidth() - $completeBars - \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::length(\ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::removeDecoration($output->getFormatter(), $bar->getProgressCharacter()));
                $display .= $bar->getProgressCharacter() . \str_repeat($bar->getEmptyBarCharacter(), $emptyBars);
            }
            return $display;
        }, 'elapsed' => function (self $bar) {
            return \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::formatTime(\time() - $bar->getStartTime());
        }, 'remaining' => function (self $bar) {
            if (!$bar->getMaxSteps()) {
                throw new \ECSPrefix20210515\Symfony\Component\Console\Exception\LogicException('Unable to display the remaining time if the maximum number of steps is not set.');
            }
            return \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::formatTime($bar->getRemaining());
        }, 'estimated' => function (self $bar) {
            if (!$bar->getMaxSteps()) {
                throw new \ECSPrefix20210515\Symfony\Component\Console\Exception\LogicException('Unable to display the estimated time if the maximum number of steps is not set.');
            }
            return \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::formatTime($bar->getEstimated());
        }, 'memory' => function (self $bar) {
            return \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::formatMemory(\memory_get_usage(\true));
        }, 'current' => function (self $bar) {
            return \str_pad($bar->getProgress(), $bar->getStepWidth(), ' ', \STR_PAD_LEFT);
        }, 'max' => function (self $bar) {
            return $bar->getMaxSteps();
        }, 'percent' => function (self $bar) {
            return \floor($bar->getProgressPercent() * 100);
        }];
    }
    /**
     * @return mixed[]
     */
    private static function initFormats()
    {
        return ['normal' => ' %current%/%max% [%bar%] %percent:3s%%', 'normal_nomax' => ' %current% [%bar%]', 'verbose' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%', 'verbose_nomax' => ' %current% [%bar%] %elapsed:6s%', 'very_verbose' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%', 'very_verbose_nomax' => ' %current% [%bar%] %elapsed:6s%', 'debug' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%', 'debug_nomax' => ' %current% [%bar%] %elapsed:6s% %memory:6s%'];
    }
    /**
     * @return string
     */
    private function buildLine()
    {
        $regex = "{%([a-z\\-_]+)(?:\\:([^%]+))?%}i";
        $callback = function ($matches) {
            if ($formatter = $this::getPlaceholderFormatterDefinition($matches[1])) {
                $text = $formatter($this, $this->output);
            } elseif (isset($this->messages[$matches[1]])) {
                $text = $this->messages[$matches[1]];
            } else {
                return $matches[0];
            }
            if (isset($matches[2])) {
                $text = \sprintf('%' . $matches[2], $text);
            }
            return $text;
        };
        $line = \preg_replace_callback($regex, $callback, $this->format);
        // gets string length for each sub line with multiline format
        $linesLength = \array_map(function ($subLine) {
            return \ECSPrefix20210515\Symfony\Component\Console\Helper\Helper::strlenWithoutDecoration($this->output->getFormatter(), \rtrim($subLine, "\r"));
        }, \explode("\n", $line));
        $linesWidth = \max($linesLength);
        $terminalWidth = $this->terminal->getWidth();
        if ($linesWidth <= $terminalWidth) {
            return $line;
        }
        $this->setBarWidth($this->barWidth - $linesWidth + $terminalWidth);
        return \preg_replace_callback($regex, $callback, $this->format);
    }
}
