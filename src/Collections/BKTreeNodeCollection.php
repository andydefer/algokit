<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BKTreeNodeRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class BKTreeNodeCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BKTreeNodeRecord::class);
    }
}
