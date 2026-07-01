<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a value to be inserted into a Bloom Filter.
 *
 * Contains the value and optional context for membership testing.
 */
final class BloomFilterRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $context = null,
    ) {}
}
