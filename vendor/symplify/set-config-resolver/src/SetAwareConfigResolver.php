<?php

namespace ECSPrefix20210515\Symplify\SetConfigResolver;

use ECSPrefix20210515\Symplify\SetConfigResolver\Config\SetsParameterResolver;
use ECSPrefix20210515\Symplify\SetConfigResolver\Contract\SetProviderInterface;
use ECSPrefix20210515\Symplify\SmartFileSystem\SmartFileInfo;
/**
 * @see \Symplify\SetConfigResolver\Tests\ConfigResolver\SetAwareConfigResolverTest
 */
final class SetAwareConfigResolver extends \ECSPrefix20210515\Symplify\SetConfigResolver\AbstractConfigResolver
{
    /**
     * @var SetsParameterResolver
     */
    private $setsParameterResolver;
    public function __construct(\ECSPrefix20210515\Symplify\SetConfigResolver\Contract\SetProviderInterface $setProvider)
    {
        $setResolver = new \ECSPrefix20210515\Symplify\SetConfigResolver\SetResolver($setProvider);
        $this->setsParameterResolver = new \ECSPrefix20210515\Symplify\SetConfigResolver\Config\SetsParameterResolver($setResolver);
        parent::__construct();
    }
    /**
     * @param SmartFileInfo[] $fileInfos
     * @return mixed[]
     */
    public function resolveFromParameterSetsFromConfigFiles(array $fileInfos)
    {
        return $this->setsParameterResolver->resolveFromFileInfos($fileInfos);
    }
}
