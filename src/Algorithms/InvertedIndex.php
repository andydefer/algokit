<?php

declare(strict_types=1);

namespace AndyDefer\AlgoKIT\Algorithms;

use AndyDefer\AlgoKIT\Collections\InvertedIndexCollection;
use AndyDefer\AlgoKIT\Collections\InvertedIndexResultCollection;
use AndyDefer\AlgoKIT\Collections\InvertedIndexSearchCollection;
use AndyDefer\AlgoKIT\Contracts\Algorithms\InvertedIndexInterface;
use AndyDefer\AlgoKIT\Records\InvertedIndexFullRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexResultRecord;
use AndyDefer\AlgoKIT\Records\InvertedIndexStatsRecord;
use AndyDefer\DomainStructures\Collections\Utility\StringTypedCollection;
use AndyDefer\DomainStructures\Utils\StrictAssociative;
use AndyDefer\StorageKit\Contracts\Storage\StorageInterface;

final class InvertedIndex implements InvertedIndexInterface
{
    private string $key;

    public function __construct(
        private StorageInterface $storage,
        string $key = 'inverted_index'
    ) {
        $this->key = $key;

        if (! $this->storage->exists($this->key)) {
            $this->storage->set($this->key, []);
        }
    }

    public function add(InvertedIndexRecord $record): void
    {
        $index = $this->getIndex();
        $documentId = $record->document_id;
        $tokens = array_unique($record->tokens->toArray());

        foreach ($tokens as $token) {
            if (! isset($index[$token])) {
                $index[$token] = [];
            }

            if (! in_array($documentId, $index[$token], true)) {
                $index[$token][] = $documentId;
            }
        }

        $this->saveIndex($index);
    }

    public function addBatch(InvertedIndexCollection $collection): void
    {
        $index = $this->getIndex();

        foreach ($collection as $record) {
            $documentId = $record->document_id;
            $tokens = array_unique($record->tokens->toArray());

            foreach ($tokens as $token) {
                if (! isset($index[$token])) {
                    $index[$token] = [];
                }

                if (! in_array($documentId, $index[$token], true)) {
                    $index[$token][] = $documentId;
                }
            }
        }

        $this->saveIndex($index);
    }

    public function search(string $token): StringTypedCollection
    {
        $index = $this->getIndex();
        $documentIds = $index[$token] ?? [];

        return StringTypedCollection::from($documentIds);
    }

    public function searchBatch(InvertedIndexSearchCollection $collection): InvertedIndexResultCollection
    {
        $index = $this->getIndex();
        $results = new InvertedIndexResultCollection;

        foreach ($collection as $record) {
            $documentIds = $index[$record->token] ?? [];
            $results->add(new InvertedIndexResultRecord(
                token: $record->token,
                document_ids: StringTypedCollection::from($documentIds),
            ));
        }

        return $results;
    }

    public function remove(string $documentId): void
    {
        $index = $this->getIndex();

        foreach ($index as $token => $documentIds) {
            $index[$token] = array_values(array_filter(
                $documentIds,
                fn ($id) => $id !== $documentId
            ));

            if (empty($index[$token])) {
                unset($index[$token]);
            }
        }

        $this->saveIndex($index);
    }

    public function removeToken(string $token): void
    {
        $index = $this->getIndex();
        unset($index[$token]);
        $this->saveIndex($index);
    }

    public function getDocumentCount(string $token): int
    {
        $index = $this->getIndex();

        return count($index[$token] ?? []);
    }

    public function getTotalTokens(): int
    {
        $index = $this->getIndex();

        return count($index);
    }

    public function getTokenFrequency(string $token): int
    {
        return $this->getDocumentCount($token);
    }

    public function getAllTokens(): StringTypedCollection
    {
        $index = $this->getIndex();

        return StringTypedCollection::from(array_keys($index));
    }

    public function getAll(): InvertedIndexFullRecord
    {
        return new InvertedIndexFullRecord(
            index: StrictAssociative::from($this->getIndex())
        );
    }

    public function getStats(): InvertedIndexStatsRecord
    {
        $index = $this->getIndex();
        $totalDocuments = 0;
        $maxFrequency = 0;
        $totalEntries = 0;

        foreach ($index as $documentIds) {
            $count = count($documentIds);
            $totalDocuments += $count;
            $totalEntries++;
            $maxFrequency = max($maxFrequency, $count);
        }

        return new InvertedIndexStatsRecord(
            total_tokens: count($index),
            total_document_entries: $totalDocuments,
            max_token_frequency: $maxFrequency,
            avg_token_frequency: $totalEntries > 0 ? round($totalDocuments / $totalEntries, 2) : 0,
        );
    }

    public function clear(): void
    {
        $this->storage->delete($this->key);
        $this->storage->set($this->key, []);
    }

    private function getIndex(): array
    {
        return $this->storage->get($this->key, []);
    }

    private function saveIndex(array $index): void
    {
        $this->storage->set($this->key, $index);
    }
}
