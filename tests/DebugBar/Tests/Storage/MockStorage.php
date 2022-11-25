<?php

namespace DebugBar\Tests\Storage;

use DebugBar\Storage\StorageInterface;

class MockStorage implements StorageInterface
{
    public $data;

    #[\ReturnTypeWillChange] public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    #[\ReturnTypeWillChange] public function save($id, $data)
    {
        $this->data[$id] = $data;
    }

    #[\ReturnTypeWillChange] public function get($id)
    {
        return $this->data[$id];
    }

    #[\ReturnTypeWillChange] public function find(array $filters = array(), $max = 20, $offset = 0)
    {
        return array_slice($this->data, $offset, $max);
    }

    #[\ReturnTypeWillChange] public function clear()
    {
        $this->data = array();
    }
}
