<?php

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

class TopKRecord extends AbstractRecord
{
    public function __construct(
        public string $value,
        public int $increment = 1,
        public ?string $context = null
    ) {}
}
