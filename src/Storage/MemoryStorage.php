<?php

namespace AndyDefer\AlgoKIT\Storage;

class MemoryStorage implements StorageInterface
{
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function delete(string $key): bool
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);

            return true;
        }

        return false;
    }

    public function exists(string $key): bool
    {
        return isset($this->data[$key]);
    }
}
