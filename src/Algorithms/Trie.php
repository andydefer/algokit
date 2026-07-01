<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\TrieCollection;
use AndyDefer\AlgoKIT\Collections\TrieResultCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\TrieInterface;
use AndyDefer\AlgoKIT\Records\TrieResultRecord;
use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;

/**
 * Trie (prefix tree) for efficient autocomplete and prefix-based search.
 *
 * Stores words in a tree structure where each node represents a character.
 * Words sharing a common prefix share the same path, enabling fast prefix
 * lookups in O(L) where L is the length of the prefix.
 *
 * @example
 * $trie = new Trie($storage);
 * $trie->insert('laravel');
 * $trie->insert('laragon');
 * $results = $trie->search('lar'); // Returns ['laravel', 'laragon']
 *
 * @see https://en.wikipedia.org/wiki/Trie
 */
final class Trie implements TrieInterface
{
    /** @var int Default limit for search results */
    private const DEFAULT_LIMIT = 10;

    /**
     * @param  StorageInterface  $storage  The storage backend for persistence
     * @param  string  $key  Unique key for this Trie instance in storage
     */
    public function __construct(
        private StorageInterface $storage,
        private string $key = 'trie'
    ) {
        if (! $this->storage->exists($this->key)) {
            $this->initializeStorage();
        }
    }

    /**
     * Inserts a word into the trie.
     *
     * The word is split into characters and each character is stored
     * as a node in the tree structure.
     *
     * @param  string  $word  The word to insert
     * @param  string|null  $context  Optional context for data isolation
     */
    public function insert(string $word, ?string $context = null): void
    {
        $root = $this->getRoot();
        $currentNode = &$this->getContextNode($root, $context);

        foreach (str_split($word) as $character) {
            $currentNode = &$this->ensureChildNodeExists($currentNode, $character);
        }

        $this->addWordToNode($currentNode, $word);
        $this->saveRoot($root);
    }

    /**
     * Searches for words with a given prefix.
     *
     * Traverses the trie to find all words starting with the specified prefix.
     *
     * @param  string  $prefix  The prefix to search for
     * @param  string|null  $context  Optional context for data isolation
     * @param  int  $limit  Maximum number of results to return (default: 10)
     * @return TrieResultCollection Collection of matching words
     */
    public function search(string $prefix, ?string $context = null, int $limit = self::DEFAULT_LIMIT): TrieResultCollection
    {
        $root = $this->getRoot();
        $startingNode = $this->findNode($root, $prefix, $context);
        $results = new TrieResultCollection;

        if ($startingNode === null) {
            return $results;
        }

        $words = $this->collectWords($startingNode, $prefix, $limit);

        foreach ($words as $word) {
            $results->add(new TrieResultRecord($word, $context));
        }

        return $results;
    }

    /**
     * Inserts multiple words in batch for better performance.
     *
     * @param  TrieCollection  $collection  Collection of words to insert
     */
    public function insertBatch(TrieCollection $collection): void
    {
        $root = $this->getRoot();

        foreach ($collection as $record) {
            $currentNode = &$this->getContextNode($root, $record->context);

            foreach (str_split($record->value) as $character) {
                $currentNode = &$this->ensureChildNodeExists($currentNode, $character);
            }

            $this->addWordToNode($currentNode, $record->value);
        }

        $this->saveRoot($root);
    }

    /**
     * Searches for multiple prefixes in batch.
     *
     * @param  TrieCollection  $collection  Collection of prefixes to search
     * @param  int  $limit  Maximum results per prefix (default: 10)
     * @return array<string, TrieResultCollection> Map of prefix to results
     */
    public function searchBatch(TrieCollection $collection, int $limit = self::DEFAULT_LIMIT): array
    {
        $results = [];
        $root = $this->getRoot();

        foreach ($collection as $record) {
            $node = $this->findNode($root, $record->value, $record->context);
            $resultCollection = new TrieResultCollection;

            if ($node !== null) {
                $words = $this->collectWords($node, $record->value, $limit);
                foreach ($words as $word) {
                    $resultCollection->add(new TrieResultRecord($word, $record->context));
                }
            }

            $key = $record->context !== null
                ? $record->context.':'.$record->value
                : $record->value;

            $results[$key] = $resultCollection;
        }

        return $results;
    }

    /**
     * Clears all data from the trie.
     */
    public function clear(): void
    {
        $this->storage->delete($this->key);
    }

    /**
     * Initializes the storage with empty trie structure.
     */
    private function initializeStorage(): void
    {
        $this->storage->set($this->key, ['root' => ['children' => [], 'words' => []]]);
    }

    /**
     * Retrieves the root node from storage.
     *
     * @return array{root: array, children: array, words: array}
     */
    private function &getRoot(): array
    {
        $data = $this->storage->get($this->key, ['root' => ['children' => [], 'words' => []]]);

        return $data;
    }

    /**
     * Saves the root node to storage.
     *
     * @param  array{root: array, children: array, words: array}  $root
     */
    private function saveRoot(array $root): void
    {
        $this->storage->set($this->key, $root);
    }

    /**
     * Retrieves the node for a specific context.
     *
     * @param  array  $root  The root node
     * @param  string|null  $context  The context to retrieve
     * @return array The context node
     */
    private function &getContextNode(array &$root, ?string $context): array
    {
        if ($context !== null) {
            if (! isset($root[$context])) {
                $root[$context] = ['children' => [], 'words' => []];
            }

            return $root[$context];
        }

        return $root['root'];
    }

    /**
     * Ensures a child node exists for a character.
     *
     * @param  array  &$node  The parent node
     * @param  string  $character  The character to look for
     * @return array The child node (existing or newly created)
     */
    private function &ensureChildNodeExists(array &$node, string $character): array
    {
        if (! isset($node['children'][$character])) {
            $node['children'][$character] = ['children' => [], 'words' => []];
        }

        return $node['children'][$character];
    }

    /**
     * Adds a word to a node's word list.
     *
     * @param  array  &$node  The node to add the word to
     * @param  string  $word  The word to add
     */
    private function addWordToNode(array &$node, string $word): void
    {
        if (! in_array($word, $node['words'], true)) {
            $node['words'][] = $word;
        }
    }

    /**
     * Finds the node for a given prefix.
     *
     * @param  array  $root  The root node
     * @param  string  $prefix  The prefix to search for
     * @param  string|null  $context  Optional context for data isolation
     * @return array|null The found node or null if not found
     */
    private function findNode(array $root, string $prefix, ?string $context = null): ?array
    {
        $currentNode = &$this->getContextNode($root, $context);

        foreach (str_split($prefix) as $character) {
            if (! isset($currentNode['children'][$character])) {
                return null;
            }

            $currentNode = &$currentNode['children'][$character];
        }

        return $currentNode;
    }

    /**
     * Collects all words under a given node.
     *
     * @param  array  $node  The starting node
     * @param  string  $prefix  The current prefix
     * @param  int  $limit  Maximum number of results
     * @return array<int, string> List of collected words
     */
    private function collectWords(array $node, string $prefix, int $limit): array
    {
        $results = [];

        $this->collectWordsRecursive($node, $prefix, $limit, $results);

        return $results;
    }

    /**
     * Recursively collects words under a node.
     *
     * @param  array  $node  The current node
     * @param  string  $prefix  The current prefix
     * @param  int  $limit  Maximum number of results
     * @param  array<int, string>  &$results  Collected results
     */
    private function collectWordsRecursive(array $node, string $prefix, int $limit, array &$results): void
    {
        foreach ($node['words'] as $word) {
            $results[] = $word;
            if (count($results) >= $limit) {
                return;
            }
        }

        foreach ($node['children'] as $character => $childNode) {
            if (count($results) >= $limit) {
                break;
            }

            $this->collectWordsRecursive($childNode, $prefix.$character, $limit, $results);
        }
    }
}
