<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

/**
 * HTTP driver for native php
 */
class PhpHttpDriver implements HttpDriverInterface
{
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    public function isSessionStarted(): bool
    {
        return isset($_SESSION);
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function setSessionValue($name, $value): void
    {
        $_SESSION[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function hasSessionValue($name): bool
    {
        return array_key_exists($name, $_SESSION);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getSessionValue($name)
    {
        return $_SESSION[$name];
    }

    /**
     * @param string $name
     */
    public function deleteSessionValue($name): void
    {
        unset($_SESSION[$name]);
    }
}
