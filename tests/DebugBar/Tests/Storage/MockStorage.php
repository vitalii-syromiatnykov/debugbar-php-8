<?php

namespace DebugBar\Tests\Storage;

use DebugBar\Storage\StorageInterface;

class MockStorage implements StorageInterface
{
    /**
     * @var mixed[]
     */
    public $data;

    #[\ReturnTypeWillChange] public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    #[\ReturnTypeWillChange] public function save($id, $data): void
    {
        $this->data[$id] = $data;
    }

    #[\ReturnTypeWillChange] public function get($id)
    {
        return $this->data[$id];
    }

    #[\ReturnTypeWillChange] public function find(array $filters = [], $max = 20, $offset = 0): array
    {
        return array_slice($this->data, $offset, $max);
    }

    #[\ReturnTypeWillChange] public function clear(): void
    {
        $this->data = [];
    }
}
