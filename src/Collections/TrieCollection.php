<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TrieRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class TrieCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TrieRecord::class);
    }
}
