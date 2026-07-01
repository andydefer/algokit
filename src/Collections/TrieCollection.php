<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TrieRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Trie values to insert.
 *
 * Contains a collection of TrieRecord objects.
 */
final class TrieCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TrieRecord::class);
    }
}
