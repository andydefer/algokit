<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BKTreeNodeRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for BK-Tree nodes.
 *
 * Contains a collection of BKTreeNodeRecord objects.
 */
final class BKTreeNodeCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BKTreeNodeRecord::class);
    }
}
