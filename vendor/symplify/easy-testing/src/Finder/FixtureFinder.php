<?php

declare (strict_types=1);
namespace ECSPrefix20220609\Symplify\EasyTesting\Finder;

use ECSPrefix20220609\Symfony\Component\Finder\Finder;
use ECSPrefix20220609\Symplify\SmartFileSystem\Finder\FinderSanitizer;
use ECSPrefix20220609\Symplify\SmartFileSystem\SmartFileInfo;
final class FixtureFinder
{
    /**
     * @var \Symplify\SmartFileSystem\Finder\FinderSanitizer
     */
    private $finderSanitizer;
    public function __construct(FinderSanitizer $finderSanitizer)
    {
        $this->finderSanitizer = $finderSanitizer;
    }
    /**
     * @param string[] $sources
     * @return SmartFileInfo[]
     */
    public function find(array $sources) : array
    {
        $finder = new Finder();
        $finder->files()->in($sources)->name('*.php.inc')->path('Fixture')->sortByName();
        return $this->finderSanitizer->sanitize($finder);
    }
}
