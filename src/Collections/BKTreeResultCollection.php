<?php

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\BKTreeResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

final class BKTreeResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(BKTreeResultRecord::class);
    }
}
