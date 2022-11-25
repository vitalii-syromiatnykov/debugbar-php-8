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

/**
 * Collects info about PHP
 */
class PhpInfoCollector extends DataCollector implements Renderable
{
    /**
     * @return string
     */
    #[\ReturnTypeWillChange] public function getName()
    {
        return 'php';
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function collect()
    {
        return array(
            'version' => implode('.', [PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION]),
            'interface' => PHP_SAPI
        );
    }

    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange] public function getWidgets()
    {
        return array(
            "php_version" => array(
                "icon" => "code",
                "tooltip" => "Version",
                "map" => "php.version",
                "default" => ""
            ),
        );
    }
}
