<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BloomFilterRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class BloomFilterCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BloomFilterRecord::class);
    }
}
