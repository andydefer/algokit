<?php

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

class HyperLogLogResultRecord extends AbstractRecord
{
    public function __construct(
        public int $count,
        public ?string $context = null
    ) {}
}
