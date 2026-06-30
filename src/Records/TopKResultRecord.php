<?php

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

class TopKResultRecord extends AbstractRecord
{
    public function __construct(
        public string $value,
        public int $count
    ) {}
}
