<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a word to be inserted into a Trie.
 *
 * Contains the word and optional context for autocomplete isolation.
 */
final class TrieRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $value,
        public readonly ?string $context = null,
    ) {}
}
