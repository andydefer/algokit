<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Contracts\Algorithms;

use AndyDefer\AlgoKIT\Collections\InvertedIndexCollection;
use AndyDefer\AlgoKIT\Collections\InvertedIndexResultCollection;
use AndyDefer\AlgoKIT\Collections\InvertedIndexSearchCollection;
use AndyDefer\AlgoKIT\Records\InvertedIndexFullRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexStatsRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;

/**
 * Interface for Inverted Index data structure.
 *
 * Maps terms to the documents that contain them, enabling efficient
 * full-text search and term-based document retrieval.
 *
 * @example
 * $index = new InvertedIndex($storage);
 * $index->add(InvertedIndexRecord::from(['document_id' => 'doc_1', 'tokens' => ['php', 'laravel']]));
 * $results = $index->search('php'); // Returns ['doc_1']
 */
interface InvertedIndexInterface
{
    /**
     * Adds a document to the inverted index.
     *
     * @param  InvertedIndexRecord  $record  The document record containing ID and tokens
     */
    public function add(InvertedIndexRecord $record): void;

    /**
     * Adds multiple documents to the inverted index in batch.
     *
     * @param  InvertedIndexCollection  $collection  Collection of document records
     */
    public function addBatch(InvertedIndexCollection $collection): void;

    /**
     * Searches for documents containing a specific token.
     *
     * @param  string  $token  The token to search for
     * @return StringTypedCollection Collection of document IDs
     */
    public function search(string $token): StringTypedCollection;

    /**
     * Searches for multiple tokens in batch.
     *
     * @param  InvertedIndexSearchCollection  $collection  Collection of token searches
     * @return InvertedIndexResultCollection Collection of search results
     */
    public function searchBatch(InvertedIndexSearchCollection $collection): InvertedIndexResultCollection;

    /**
     * Removes a document from the index.
     *
     * @param  string  $documentId  The document ID to remove
     */
    public function remove(string $documentId): void;

    /**
     * Removes a token from the index entirely.
     *
     * @param  string  $token  The token to remove
     */
    public function removeToken(string $token): void;

    /**
     * Gets the number of documents containing a specific token.
     *
     * @param  string  $token  The token to check
     * @return int Number of documents
     */
    public function getDocumentCount(string $token): int;

    /**
     * Gets the total number of unique tokens in the index.
     *
     * @return int Total unique tokens
     */
    public function getTotalTokens(): int;

    /**
     * Gets the frequency of a specific token (alias of getDocumentCount).
     *
     * @param  string  $token  The token to check
     * @return int Token frequency
     */
    public function getTokenFrequency(string $token): int;

    /**
     * Gets all unique tokens in the index.
     *
     * @return StringTypedCollection Collection of all tokens
     */
    public function getAllTokens(): StringTypedCollection;

    /**
     * Gets the full inverted index data.
     *
     * @return InvertedIndexFullRecord Complete index data
     */
    public function getAll(): InvertedIndexFullRecord;

    /**
     * Gets statistics about the index.
     *
     * @return InvertedIndexStatsRecord Index statistics
     */
    public function getStats(): InvertedIndexStatsRecord;

    /**
     * Clears all data from the index.
     */
    public function clear(): void;
}
