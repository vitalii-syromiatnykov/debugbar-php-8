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

interface StorageInterface
{
    /**
     * Saves collected data
     *
     * @param string $id
     * @param string $data
     */
    public function save($id, $data);

    /**
     * Returns collected data with the specified id
     *
     * @param string $id
     * @return array
     */
    public function get($id);

    /**
     * Returns a metadata about collected data
     *
     * @param integer $max
     * @param integer $offset
     * @return array
     */
    public function find(array $filters = [], $max = 20, $offset = 0);

    /**
     * Clears all the collected data
     */
    public function clear();
}
