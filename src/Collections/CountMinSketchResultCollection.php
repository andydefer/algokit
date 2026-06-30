<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\CountMinSketchResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class CountMinSketchResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(CountMinSketchResultRecord::class);
    }
}
