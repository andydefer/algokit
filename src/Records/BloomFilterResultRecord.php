<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a membership test result from Bloom Filter.
 *
 * Contains the value, whether it probably exists, and optional context.
 */
final class BloomFilterResultRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $value,
        public readonly bool $exists,
        public readonly ?string $context = null,
    ) {}
}
