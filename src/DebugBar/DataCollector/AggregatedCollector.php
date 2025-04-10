<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

use ArrayAccess;
use DebugBar\DebugBarException;

/**
 * Aggregates data from multiple collectors
 *
 * <code>
 * $aggcollector = new AggregateCollector('foobar');
 * $aggcollector->addCollector(new MessagesCollector('msg1'));
 * $aggcollector->addCollector(new MessagesCollector('msg2'));
 * $aggcollector['msg1']->addMessage('hello world');
 * </code>
 */
class AggregatedCollector implements DataCollectorInterface, ArrayAccess
{
    protected $collectors = [];

    /**
     * @param string $name
     * @param string $mergeProperty
     * @param boolean $sort
     */
    #[\ReturnTypeWillChange]
    public function __construct(protected $name, protected $mergeProperty = null, protected $sort = false)
    {
    }

    #[\ReturnTypeWillChange] public function addCollector(DataCollectorInterface $collector): void
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function getCollectors()
    {
        return $this->collectors;
    }

    /**
     * Merge data from one of the key/value pair of the collected data
     *
     * @param string $property
     */
    #[\ReturnTypeWillChange] public function setMergeProperty($property): void
    {
        $this->mergeProperty = $property;
    }

    /**
     * @return string
     */
    #[\ReturnTypeWillChange] public function getMergeProperty()
    {
        return $this->mergeProperty;
    }

    /**
     * Sorts the collected data
     *
     * If true, sorts using sort()
     * If it is a string, sorts the data using the value from a key/value pair of the array
     *
     * @param bool|string $sort
     */
    #[\ReturnTypeWillChange] public function setSort($sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @return bool|string
     */
    #[\ReturnTypeWillChange] public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function collect()
    {
        $aggregate = [];
        foreach ($this->collectors as $collector) {
            $data = $collector->collect();
            if ($this->mergeProperty !== null) {
                $data = $data[$this->mergeProperty];
            }

            $aggregate = array_merge($aggregate, $data);
        }

        return $this->sort($aggregate);
    }

    /**
     * Sorts the collected data
     *
     * @param array $data
     * @return array
     */
    protected function sort($data)
    {
        if (is_string($this->sort)) {
            $p = $this->sort;
            usort($data, fn($a, $b): int => $a[$p] <=> $b[$p]);
        } elseif ($this->sort === true) {
            sort($data);
        }

        return $data;
    }

    /**
     * @return string
     */
    #[\ReturnTypeWillChange] public function getName()
    {
        return $this->name;
    }

    // --------------------------------------------
    // ArrayAccess implementation

    /**
     * @param mixed $key
     * @param mixed $value
     * @throws DebugBarException
     */
    #[\ReturnTypeWillChange] public function offsetSet($key, $value): void
    {
        throw new DebugBarException("AggregatedCollector[] is read-only");
    }

    /**
     * @param mixed $key
     * @return mixed
     */

    #[\ReturnTypeWillChange] public function offsetGet($key)
    {
        return $this->collectors[$key];
    }

    /**
     * @param mixed $key
     */
    #[\ReturnTypeWillChange] public function offsetExists($key): bool
    {
        return isset($this->collectors[$key]);
    }

    /**
     * @param mixed $key
     * @throws DebugBarException
     */
    #[\ReturnTypeWillChange] public function offsetUnset($key): void
    {
        throw new DebugBarException("AggregatedCollector[] is read-only");
    }
}
