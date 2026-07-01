<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\CountMinSketchResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Count-Min Sketch frequency results.
 *
 * Contains a collection of CountMinSketchResultRecord objects.
 */
final class CountMinSketchResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(CountMinSketchResultRecord::class);
    }
}
