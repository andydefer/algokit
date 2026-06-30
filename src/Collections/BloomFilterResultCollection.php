<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BloomFilterResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class BloomFilterResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BloomFilterResultRecord::class);
    }
}
