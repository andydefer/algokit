<?php

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\AlgoKIT\Collections\BKTreeNodeCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

class BKTreeNodeRecord extends AbstractRecord
{
    public function __construct(
        public string $word,
        public BKTreeNodeCollection $children
    ) {}
}
