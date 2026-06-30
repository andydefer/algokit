<?php

namespace AndyDefer\AlgoKIT\Storage;

interface StorageInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function delete(string $key): bool;

    public function exists(string $key): bool;
}
