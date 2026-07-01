<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a frequency result from Count-Min Sketch.
 *
 * Contains the value, its estimated frequency, and optional context.
 */
final class CountMinSketchResultRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $value,
        public readonly int $count,
        public readonly ?string $context = null,
    ) {}
}
