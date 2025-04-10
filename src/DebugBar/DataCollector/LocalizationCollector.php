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
 * Collects info about the current localization state
 */
class LocalizationCollector extends DataCollector implements Renderable
{
    /**
     * Get the current locale
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getLocale(): string|false
    {
        return setlocale(LC_ALL, 0);
    }

    /**
     * Get the current translations domain
     */
    #[\ReturnTypeWillChange] public function getDomain(): string
    {
        return textdomain();
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        return [
          'locale' => $this->getLocale(),
          'domain' => $this->getDomain(),
        ];
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'localization';
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            'domain' => [
                'icon' => 'bookmark',
                'map'  => 'localization.domain',
            ],
            'locale' => [
                'icon' => 'flag',
                'map'  => 'localization.locale',
            ]
        ];
    }
}
