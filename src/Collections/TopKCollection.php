<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Collections;

use AndyDefer\AlgoKIT\Records\TopKRecord;
use AndyDefer\DomainStructures\Abstracts\AbstractTypedCollection;

/**
 * Typed collection for Top-K values to add.
 *
 * Contains a collection of TopKRecord objects.
 */
final class TopKCollection extends AbstractTypedCollection
{
    public function __construct()
    {
        parent::__construct(TopKRecord::class);
    }
}
