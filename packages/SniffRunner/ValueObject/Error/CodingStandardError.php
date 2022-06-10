<?php

declare (strict_types=1);
namespace Symplify\EasyCodingStandard\SniffRunner\ValueObject\Error;

use Symplify\EasyCodingStandard\Parallel\ValueObject\Name;
use ECSPrefix20220610\Symplify\EasyParallel\Contract\SerializableInterface;
final class CodingStandardError implements SerializableInterface
{
    /**
     * @var int
     */
    private $line;
    /**
     * @var string
     */
    private $message;
    /**
     * @var string
     */
    private $checkerClass;
    /**
     * @var string
     */
    private $relativeFilePath;
    public function __construct(int $line, string $message, string $checkerClass, string $relativeFilePath)
    {
        $this->line = $line;
        $this->message = $message;
        $this->checkerClass = $checkerClass;
        $this->relativeFilePath = $relativeFilePath;
    }
    public function getLine() : int
    {
        return $this->line;
    }
    public function getMessage() : string
    {
        return $this->message;
    }
    public function getCheckerClass() : string
    {
        return $this->checkerClass;
    }
    public function getFileWithLine() : string
    {
        return $this->relativeFilePath . ':' . $this->line;
    }
    public function getRelativeFilePath() : string
    {
        return $this->relativeFilePath;
    }
    /**
     * @return array{line: int, message: string, checker_class: string, relative_file_path: string}
     */
    public function jsonSerialize() : array
    {
        return [Name::LINE => $this->line, Name::MESSAGE => $this->message, Name::CHECKER_CLASS => $this->checkerClass, Name::RELATIVE_FILE_PATH => $this->relativeFilePath];
    }
    /**
     * @param array{line: int, message: string, checker_class: string, relative_file_path: string} $json
     * @return $this
     */
    public static function decode(array $json) : \ECSPrefix20220610\Symplify\EasyParallel\Contract\SerializableInterface
    {
        return new self($json[Name::LINE], $json[Name::MESSAGE], $json[Name::CHECKER_CLASS], $json[Name::RELATIVE_FILE_PATH]);
    }
}
