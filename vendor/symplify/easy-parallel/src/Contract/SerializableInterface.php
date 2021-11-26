<?php

declare (strict_types=1);
namespace ECSPrefix20211126\Symplify\EasyParallel\Contract;

use JsonSerializable;
interface SerializableInterface extends \JsonSerializable
{
    /**
     * @param array<string, mixed> $json
     */
    public static function decode($json) : self;
}