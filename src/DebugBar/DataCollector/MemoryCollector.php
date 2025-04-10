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
 * Collects info about memory usage
 */
class MemoryCollector extends DataCollector implements Renderable
{
    protected $realUsage = false;

    protected $peakUsage = 0;

    /**
     * Returns whether total allocated memory page size is used instead of actual used memory size
     * by the application.  See $real_usage parameter on memory_get_peak_usage for details.
     *
     * @return bool
     */
    #[\ReturnTypeWillChange] public function getRealUsage()
    {
        return $this->realUsage;
    }

    /**
     * Sets whether total allocated memory page size is used instead of actual used memory size
     * by the application.  See $real_usage parameter on memory_get_peak_usage for details.
     *
     * @param bool $realUsage
     */
    #[\ReturnTypeWillChange] public function setRealUsage($realUsage): void
    {
        $this->realUsage = $realUsage;
    }

    /**
     * Returns the peak memory usage
     *
     * @return integer
     */
    #[\ReturnTypeWillChange] public function getPeakUsage()
    {
        return $this->peakUsage;
    }

    /**
     * Updates the peak memory usage value
     */
    #[\ReturnTypeWillChange] public function updatePeakUsage(): void
    {
        $this->peakUsage = memory_get_peak_usage($this->realUsage);
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        $this->updatePeakUsage();
        return [
            'peak_usage' => $this->peakUsage,
            'peak_usage_str' => $this->getDataFormatter()->formatBytes($this->peakUsage, 0)
        ];
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'memory';
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            "memory" => [
                "icon" => "cogs",
                "tooltip" => "Memory Usage",
                "map" => "memory.peak_usage_str",
                "default" => "'0B'"
            ]
        ];
    }
}
