<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\CountMinSketchRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Count-Min Sketch values to add.
 *
 * Contains a collection of CountMinSketchRecord objects.
 */
final class CountMinSketchCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(CountMinSketchRecord::class);
    }
}
