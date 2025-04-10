<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Storage;

/**
 * Stores collected data into Redis
 */
class RedisStorage implements StorageInterface
{
    /**
     * @param  \Predis\Client|\Redis $redis Redis Client
     * @param string $hash
     */
    #[\ReturnTypeWillChange]
    public function __construct(protected $redis, protected $hash = 'phpdebugbar')
    {
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function save($id, $data): void
    {
        $this->redis->hSet($this->hash . ':meta', $id, serialize($data['__meta']));
        unset($data['__meta']);
        $this->redis->hSet($this->hash . ':data', $id, serialize($data));
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function get($id): array
    {
        return array_merge(unserialize($this->redis->hGet($this->hash . ':data', $id)),
            ['__meta' => unserialize($this->redis->hGet($this->hash . ':meta', $id))]);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function find(array $filters = [], $max = 20, $offset = 0): array
    {
        $results = [];
        $cursor = "0";
        $isPhpRedis = $this->redis::class === 'Redis';

        do {
            if ($isPhpRedis) {
                $data = $this->redis->hScan($this->hash . ':meta', $cursor);
            } else {
                [$cursor, $data] = $this->redis->hScan($this->hash . ':meta', $cursor);
            }

            foreach ($data as $meta) {
                if (($meta = unserialize($meta)) && $this->filter($meta, $filters)) {
                    $results[] = $meta;
                }
            }
        } while($cursor);

        usort($results, static fn($a, $b): int => $b['utime'] <=> $a['utime']);

        return array_slice($results, $offset, $max);
    }

    /**
     * Filter the metadata for matches.
     */
    protected function filter($meta, $filters): bool
    {
        foreach ($filters as $key => $value) {
            if (!isset($meta[$key]) || fnmatch($value, $meta[$key]) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function clear(): void
    {
        $this->redis->del($this->hash . ':data');
        $this->redis->del($this->hash . ':meta');
    }
}
