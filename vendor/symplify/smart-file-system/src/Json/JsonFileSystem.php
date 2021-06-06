<?php

declare (strict_types=1);
namespace ECSPrefix20210606\Symplify\SmartFileSystem\Json;

use ECSPrefix20210606\Nette\Utils\Arrays;
use ECSPrefix20210606\Nette\Utils\Json;
use ECSPrefix20210606\Symplify\SmartFileSystem\FileSystemGuard;
use ECSPrefix20210606\Symplify\SmartFileSystem\SmartFileSystem;
/**
 * @see \Symplify\SmartFileSystem\Tests\Json\JsonFileSystem\JsonFileSystemTest
 */
final class JsonFileSystem
{
    /**
     * @var FileSystemGuard
     */
    private $fileSystemGuard;
    /**
     * @var SmartFileSystem
     */
    private $smartFileSystem;
    public function __construct(\ECSPrefix20210606\Symplify\SmartFileSystem\FileSystemGuard $fileSystemGuard, \ECSPrefix20210606\Symplify\SmartFileSystem\SmartFileSystem $smartFileSystem)
    {
        $this->fileSystemGuard = $fileSystemGuard;
        $this->smartFileSystem = $smartFileSystem;
    }
    /**
     * @return mixed[]
     */
    public function loadFilePathToJson(string $filePath) : array
    {
        $this->fileSystemGuard->ensureFileExists($filePath, __METHOD__);
        $fileContent = $this->smartFileSystem->readFile($filePath);
        return \ECSPrefix20210606\Nette\Utils\Json::decode($fileContent, \ECSPrefix20210606\Nette\Utils\Json::FORCE_ARRAY);
    }
    /**
     * @param array<string, mixed> $jsonArray
     * @return void
     */
    public function writeJsonToFilePath(array $jsonArray, string $filePath)
    {
        $jsonContent = \ECSPrefix20210606\Nette\Utils\Json::encode($jsonArray, \ECSPrefix20210606\Nette\Utils\Json::PRETTY) . \PHP_EOL;
        $this->smartFileSystem->dumpFile($filePath, $jsonContent);
    }
    /**
     * @param array<string, mixed> $newJsonArray
     * @return void
     */
    public function mergeArrayToJsonFile(string $filePath, array $newJsonArray)
    {
        $jsonArray = $this->loadFilePathToJson($filePath);
        $newComposerJsonArray = \ECSPrefix20210606\Nette\Utils\Arrays::mergeTree($jsonArray, $newJsonArray);
        $this->writeJsonToFilePath($newComposerJsonArray, $filePath);
    }
}
