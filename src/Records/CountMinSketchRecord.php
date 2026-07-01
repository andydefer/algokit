<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a value to be added to Count-Min Sketch.
 *
 * Contains the value and optional context for frequency counting.
 */
final class CountMinSketchRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $context = null,
    ) {}
}
