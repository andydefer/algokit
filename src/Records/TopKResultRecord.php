<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a Top-K frequent element result.
 *
 * Contains the value and its frequency count.
 */
final class TopKResultRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $value,
        public readonly int $count,
    ) {}
}
