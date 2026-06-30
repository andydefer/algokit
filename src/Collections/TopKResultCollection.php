<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TopKResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class TopKResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TopKResultRecord::class);
    }
}
