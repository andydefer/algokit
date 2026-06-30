<?php

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

class BKTreeResultRecord extends AbstractRecord
{
    public function __construct(
        public string $word,
        public int $distance
    ) {}
}
