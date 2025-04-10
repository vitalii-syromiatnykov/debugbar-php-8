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
 * Stores collected data into files
 */
class FileStorage implements StorageInterface
{
    protected string $dirname;

    /**
     * @param string $dirname Directories where to store files
     */
    #[\ReturnTypeWillChange] public function __construct($dirname)
    {
        $this->dirname = rtrim($dirname, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function save($id, $data): void
    {
        if (!file_exists($this->dirname)) {
            mkdir($this->dirname, 0777, true);
        }

        file_put_contents($this->makeFilename($id), json_encode($data));
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function get($id): mixed
    {
        return json_decode(file_get_contents($this->makeFilename($id)), true);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function find(array $filters = [], $max = 20, $offset = 0): array
    {
        //Loop through all .json files and remember the modified time and id.
        $files = [];
        foreach (new \DirectoryIterator($this->dirname) as $file) {
            if ($file->getExtension() === 'json') {
                $files[] = [
                    'time' => $file->getMTime(),
                    'id' => $file->getBasename('.json')
                ];
            }
        }

        //Sort the files, newest first
        usort($files, fn($a, $b): int => $a['time'] <=> $b['time']);

        //Load the metadata and filter the results.
        $results = [];
        $i = 0;
        foreach ($files as $file) {
            //When filter is empty, skip loading the offset
            if ($i++ < $offset && $filters === []) {
                $results[] = null;
                continue;
            }

            $data = $this->get($file['id']);
            $meta = $data['__meta'];
            unset($data);
            if ($this->filter($meta, $filters)) {
                $results[] = $meta;
            }

            if (count($results) >= ($max + $offset)) {
                break;
            }
        }

        return array_slice($results, $offset, $max);
    }

    /**
     * Filter the metadata for matches.
     *
     * @param  array $meta
     * @param  array $filters
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
        foreach (new \DirectoryIterator($this->dirname) as $file) {
            if (!str_starts_with($file->getFilename(), '.')) {
                unlink($file->getPathname());
            }
        }
    }

    /**
     * @param  string $id
     */
    #[\ReturnTypeWillChange] public function makeFilename($id): string
    {
        return $this->dirname . basename($id). ".json";
    }
}
