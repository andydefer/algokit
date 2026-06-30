<?php

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

class CountMinSketchRecord extends AbstractRecord
{
    public function __construct(
        public string $value,
        public ?string $context = null
    ) {}
}
