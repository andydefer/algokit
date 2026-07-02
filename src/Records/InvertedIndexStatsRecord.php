<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Statistics record for Inverted Index.
 *
 * Provides metrics about the index including token counts and frequencies.
 */
final class InvertedIndexStatsRecord extends AbstractRecord
{
    public function __construct(
        /** Total number of unique tokens in the index. */
        public readonly int $total_tokens,

        /** Total number of document entries across all tokens. */
        public readonly int $total_document_entries,

        /** Maximum number of documents containing a single token. */
        public readonly int $max_token_frequency,

        /** Average number of documents per token. */
        public readonly float $avg_token_frequency,
    ) {}
}
