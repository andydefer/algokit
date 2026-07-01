<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a cardinality result from HyperLogLog.
 *
 * Contains the estimated count and optional context.
 */
final class HyperLogLogResultRecord extends AbstractRecord
{
    public function __construct(
        public readonly int $count,
        public readonly ?string $context = null,
    ) {}
}
