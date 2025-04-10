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

use PDO;

/**
 * Stores collected data into a database using PDO
 */
class PdoStorage implements StorageInterface
{
    protected $sqlQueries = [
        'save' => "INSERT INTO %tablename% (id, data, meta_utime, meta_datetime, meta_uri, meta_ip, meta_method) VALUES (?, ?, ?, ?, ?, ?, ?)",
        'get' => "SELECT data FROM %tablename% WHERE id = ?",
        'find' => "SELECT data FROM %tablename% %where% ORDER BY meta_datetime DESC LIMIT %limit% OFFSET %offset%",
        'clear' => "DELETE FROM %tablename%"
    ];

    /**
     * @param \PDO $pdo The PDO instance
     * @param string $tableName
     */
    #[\ReturnTypeWillChange] public function __construct(protected \PDO $pdo, protected $tableName = 'phpdebugbar', array $sqlQueries = [])
    {
        $this->setSqlQueries($sqlQueries);
    }

    /**
     * Sets the sql queries to be used
     */
    #[\ReturnTypeWillChange] public function setSqlQueries(array $queries): void
    {
        $this->sqlQueries = array_merge($this->sqlQueries, $queries);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function save($id, $data): void
    {
        $sql = $this->getSqlQuery('save');
        $stmt = $this->pdo->prepare($sql);
        $meta = $data['__meta'];
        $stmt->execute([$id, serialize($data), $meta['utime'], $meta['datetime'], $meta['uri'], $meta['ip'], $meta['method']]);
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function get($id)
    {
        $sql = $this->getSqlQuery('get');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        if (($data = $stmt->fetchColumn(0)) !== false) {
            return unserialize($data);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @return mixed[]
     */
    #[\ReturnTypeWillChange] public function find(array $filters = [], $max = 20, $offset = 0): array
    {
        $where = [];
        $params = [];
        foreach ($filters as $key => $value) {
            $where[] = sprintf('meta_%s = ?', $key);
            $params[] = $value;
        }

        $where = count($where) ? " WHERE " . implode(' AND ', $where) : '';

        $sql = $this->getSqlQuery('find', [
            'where' => $where,
            'offset' => $offset,
            'limit' => $max
        ]);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $data = unserialize($row['data']);
            $results[] = $data['__meta'];
            unset($data);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange] public function clear(): void
    {
        $this->pdo->exec($this->getSqlQuery('clear'));
    }

    /**
     * Get a SQL Query for a task, with the variables replaced
     *
     * @param  string $name
     * @return string
     */
    protected function getSqlQuery($name, array $vars = []): string|array
    {
        $sql = $this->sqlQueries[$name];
        $vars = array_merge(['tablename' => $this->tableName], $vars);
        foreach ($vars as $k => $v) {
            $sql = str_replace(sprintf('%%%s%%', $k), $v, $sql);
        }

        return $sql;
    }
}
