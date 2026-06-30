<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TrieResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class TrieResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TrieResultRecord::class);
    }
}
