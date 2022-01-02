<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace ECSPrefix20220102\Nette\Neon\Node;

use ECSPrefix20220102\Nette\Neon\Node;
/** @internal */
abstract class ArrayNode extends \ECSPrefix20220102\Nette\Neon\Node
{
    /** @var ArrayItemNode[] */
    public $items = [];
    public function toValue() : array
    {
        return \ECSPrefix20220102\Nette\Neon\Node\ArrayItemNode::itemsToArray($this->items);
    }
    public function getSubNodes() : array
    {
        $res = [];
        foreach ($this->items as &$item) {
            $res[] =& $item;
        }
        return $res;
    }
}
