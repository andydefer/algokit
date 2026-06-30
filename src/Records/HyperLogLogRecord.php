<?php

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

class HyperLogLogRecord extends AbstractRecord
{
    public function __construct(
        public string $value,
        public ?string $context = null
    ) {}
}
