<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a value to be added to Top-K tracker.
 *
 * Contains the value, increment amount, and optional context.
 */
final class TopKRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $value,
        public readonly int $increment = 1,
        public readonly ?string $context = null,
    ) {}
}
