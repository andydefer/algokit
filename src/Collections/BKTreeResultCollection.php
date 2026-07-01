<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BKTreeResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for BK-Tree fuzzy search results.
 *
 * Contains a collection of BKTreeResultRecord objects.
 */
final class BKTreeResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BKTreeResultRecord::class);
    }
}
