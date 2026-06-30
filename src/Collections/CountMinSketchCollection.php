<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\CountMinSketchRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class CountMinSketchCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(CountMinSketchRecord::class);
    }
}
