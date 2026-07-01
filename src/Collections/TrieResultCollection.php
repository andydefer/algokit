<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TrieResultRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Trie search results.
 *
 * Contains a collection of TrieResultRecord objects.
 */
final class TrieResultCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TrieResultRecord::class);
    }
}
