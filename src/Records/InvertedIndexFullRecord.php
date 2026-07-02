<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;
use AndyDefer\DomainStructures\Utils\StrictAssociative;

/**
 * Record representing the complete inverted index.
 *
 * The index maps tokens (keys) to arrays of document IDs (values).
 *
 * @example
 * $full = new InvertedIndexFullRecord(
 *     index: StrictAssociative::from([
 *         'php' => ['doc_1', 'doc_2'],
 *         'laravel' => ['doc_1'],
 *     ])
 * );
 */
final class InvertedIndexFullRecord extends AbstractRecord
{
    public function __construct(
        /** The complete inverted index mapping tokens to document IDs. */
        public readonly StrictAssociative $index,
    ) {}
}
