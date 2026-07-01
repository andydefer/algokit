<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Records;

use AndyDefer\AlgoKIT\Collections\BKTreeNodeCollection;
use AndyDefer\DomainStructures\Abstracts\AbstractRecord;

/**
 * Record representing a node in a BK-Tree.
 *
 * Contains a word and its child nodes organized by Levenshtein distance.
 */
final class BKTreeNodeRecord extends AbstractRecord
{
    public function __construct(
        public readonly string $word,
        public readonly BKTreeNodeCollection $children,
    ) {}
}
