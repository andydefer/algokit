<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TopKResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Top-K results.
 *
 * Contains a collection of TopKResultRecord objects.
 */
final class TopKResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TopKResultRecord::class);
    }
}
