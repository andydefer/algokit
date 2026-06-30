<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\HyperLogLogRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class HyperLogLogCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(HyperLogLogRecord::class);
    }
}
