<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a word found in a Trie search.
 *
 * Contains the matching word and optional context.
 */
final class TrieResultRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $word,
        public readonly ?string $context = null,
    ) {}
}
