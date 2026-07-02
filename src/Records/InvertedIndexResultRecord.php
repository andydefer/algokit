<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Record representing a search result.
 */
final class InvertedIndexResultRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $token,
        public readonly StringTypedCollection $document_ids,
    ) {}
}
