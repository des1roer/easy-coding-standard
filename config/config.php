<?php

declare (strict_types=1);
namespace ECSPrefix20220414;

use PHP_CodeSniffer\Fixer;
use PhpCsFixer\Differ\DifferInterface;
use PhpCsFixer\Differ\UnifiedDiffer;
use PhpCsFixer\WhitespacesFixerConfig;
use ECSPrefix20220414\Symfony\Component\Console\Style\SymfonyStyle;
use ECSPrefix20220414\Symfony\Component\Console\Terminal;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\Application\Version\StaticVersionResolver;
use Symplify\EasyCodingStandard\Caching\Cache;
use Symplify\EasyCodingStandard\Caching\CacheFactory;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle;
use Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyleFactory;
use Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor;
use Symplify\EasyCodingStandard\FixerRunner\WhitespacesFixerConfigFactory;
use Symplify\EasyCodingStandard\ValueObject\Option;
use ECSPrefix20220414\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory;
use ECSPrefix20220414\Symplify\PackageBuilder\Yaml\ParametersMerger;
use ECSPrefix20220414\Symplify\SmartFileSystem\FileSystemFilter;
use ECSPrefix20220414\Symplify\SmartFileSystem\FileSystemGuard;
use ECSPrefix20220414\Symplify\SmartFileSystem\Finder\FinderSanitizer;
use ECSPrefix20220414\Symplify\SmartFileSystem\Finder\SmartFinder;
use ECSPrefix20220414\Symplify\SmartFileSystem\SmartFileSystem;
use function ECSPrefix20220414\Symfony\Component\DependencyInjection\Loader\Configurator\service;
return static function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) : void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::INDENTATION, \Symplify\EasyCodingStandard\ValueObject\Option::INDENTATION_SPACES);
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::LINE_ENDING, \PHP_EOL);
    $cacheDirectory = \sys_get_temp_dir() . '/changed_files_detector%env(TEST_SUFFIX)%';
    if (\Symplify\EasyCodingStandard\Application\Version\StaticVersionResolver::PACKAGE_VERSION !== '@package_version@') {
        $cacheDirectory .= '_' . \Symplify\EasyCodingStandard\Application\Version\StaticVersionResolver::PACKAGE_VERSION;
    }
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::CACHE_DIRECTORY, $cacheDirectory);
    $cacheNamespace = \str_replace(\DIRECTORY_SEPARATOR, '_', \getcwd());
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::CACHE_NAMESPACE, $cacheNamespace);
    // parallel
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::PARALLEL, \true);
    // how many files are processed in single process
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::PARALLEL_JOB_SIZE, 60);
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::PARALLEL_MAX_NUMBER_OF_PROCESSES, 16);
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::PARALLEL_TIMEOUT_IN_SECONDS, 120);
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::PATHS, []);
    $parameters->set(\Symplify\EasyCodingStandard\ValueObject\Option::FILE_EXTENSIONS, ['php']);
    $parameters->set('env(TEST_SUFFIX)', '');
    $services = $containerConfigurator->services();
    $services->defaults()->public()->autowire()->autoconfigure();
    $services->load('Symplify\\EasyCodingStandard\\', __DIR__ . '/../src')->exclude([
        // only for "bin/ecs" file, where container does not exist yet
        __DIR__ . '/../src/Config/ECSConfig.php',
        __DIR__ . '/../src/DependencyInjection',
        __DIR__ . '/../src/Kernel',
        __DIR__ . '/../src/Exception',
        __DIR__ . '/../src/ValueObject',
        // for 3rd party tests
        __DIR__ . '/../src/Testing',
    ]);
    $services->load('Symplify\\EasyCodingStandard\\', __DIR__ . '/../packages')->exclude([__DIR__ . '/../packages/*/ValueObject/*']);
    $services->set(\Symplify\EasyCodingStandard\Caching\Cache::class)->factory([\ECSPrefix20220414\Symfony\Component\DependencyInjection\Loader\Configurator\service(\Symplify\EasyCodingStandard\Caching\CacheFactory::class), 'create']);
    $services->set(\ECSPrefix20220414\Symfony\Component\Console\Terminal::class);
    $services->set(\ECSPrefix20220414\Symplify\SmartFileSystem\FileSystemGuard::class);
    $services->set(\ECSPrefix20220414\Symplify\SmartFileSystem\Finder\FinderSanitizer::class);
    $services->set(\ECSPrefix20220414\Symplify\SmartFileSystem\SmartFileSystem::class);
    $services->set(\ECSPrefix20220414\Symplify\SmartFileSystem\Finder\SmartFinder::class);
    $services->set(\ECSPrefix20220414\Symplify\SmartFileSystem\FileSystemFilter::class);
    $services->set(\ECSPrefix20220414\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory::class);
    $services->set(\ECSPrefix20220414\Symfony\Component\Console\Style\SymfonyStyle::class)->factory([\ECSPrefix20220414\Symfony\Component\DependencyInjection\Loader\Configurator\service(\ECSPrefix20220414\Symplify\PackageBuilder\Console\Style\SymfonyStyleFactory::class), 'create']);
    $services->set(\ECSPrefix20220414\Symplify\PackageBuilder\Yaml\ParametersMerger::class);
    $services->set(\Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyle::class)->factory([\ECSPrefix20220414\Symfony\Component\DependencyInjection\Loader\Configurator\service(\Symplify\EasyCodingStandard\Console\Style\EasyCodingStandardStyleFactory::class), 'create']);
    $services->set(\PhpCsFixer\WhitespacesFixerConfig::class)->factory([\ECSPrefix20220414\Symfony\Component\DependencyInjection\Loader\Configurator\service(\Symplify\EasyCodingStandard\FixerRunner\WhitespacesFixerConfigFactory::class), 'create']);
    // code sniffer
    $services->set(\PHP_CodeSniffer\Fixer::class);
    // fixer
    $services->set(\PhpCsFixer\Differ\UnifiedDiffer::class);
    $services->alias(\PhpCsFixer\Differ\DifferInterface::class, \PhpCsFixer\Differ\UnifiedDiffer::class);
    $services->set(\Symplify\EasyCodingStandard\FixerRunner\Application\FixerFileProcessor::class);
};
