<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TopKRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class TopKCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TopKRecord::class);
    }
}
