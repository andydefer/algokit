<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Record representing a document to be indexed.
 */
final class InvertedIndexRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $document_id,
        public readonly StringTypedCollection $tokens,
    ) {}
}
