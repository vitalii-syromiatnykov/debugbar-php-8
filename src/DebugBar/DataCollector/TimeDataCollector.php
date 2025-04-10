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

use DebugBar\DebugBarException;

/**
 * Collects info about the request duration as well as providing
 * a way to log duration of any operations
 */
class TimeDataCollector extends DataCollector implements Renderable
{
    protected float $requestStartTime;

    /**
     * @var float
     */
    protected $requestEndTime;

    /**
     * @var array
     */
    protected $startedMeasures = [];

    /**
     * @var array
     */
    protected $measures = [];

    /**
     * @param float $requestStartTime
     */
    #[\ReturnTypeWillChange] public function __construct($requestStartTime = null)
    {
        if ($requestStartTime === null) {
            $requestStartTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
        }

        $this->requestStartTime = (float)$requestStartTime;
    }

    /**
     * Starts a measure
     *
     * @param string $name Internal name, used to stop the measure
     * @param string|null $label Public name
     * @param string|null $collector The source of the collector
     */
    #[\ReturnTypeWillChange] public function startMeasure($name, $label = null, $collector = null): void
    {
        $start = microtime(true);
        $this->startedMeasures[$name] = [
            'label' => $label ?: $name,
            'start' => $start,
            'collector' => $collector
        ];
    }

    /**
     * Check a measure exists
     *
     * @param string $name
     */
    #[\ReturnTypeWillChange] public function hasStartedMeasure($name): bool
    {
        return isset($this->startedMeasures[$name]);
    }

    /**
     * Stops a measure
     *
     * @param string $name
     * @param array $params
     * @throws DebugBarException
     */
    #[\ReturnTypeWillChange] public function stopMeasure($name, $params = []): void
    {
        $end = microtime(true);
        if (!$this->hasStartedMeasure($name)) {
            throw new DebugBarException(sprintf("Failed stopping measure '%s' because it hasn't been started", $name));
        }

        $this->addMeasure(
            $this->startedMeasures[$name]['label'],
            $this->startedMeasures[$name]['start'],
            $end,
            $params,
            $this->startedMeasures[$name]['collector']
        );
        unset($this->startedMeasures[$name]);
    }

    /**
     * Adds a measure
     *
     * @param string $label
     * @param float $start
     * @param float $end
     * @param array $params
     * @param string|null $collector
     */
    #[\ReturnTypeWillChange] public function addMeasure($label, $start, $end, $params = [], $collector = null): void
    {
        $this->measures[] = [
            'label' => $label,
            'start' => $start,
            'relative_start' => $start - $this->requestStartTime,
            'end' => $end,
            'relative_end' => $end - $this->requestEndTime,
            'duration' => $end - $start,
            'duration_str' => $this->getDataFormatter()->formatDuration($end - $start),
            'params' => $params,
            'collector' => $collector
        ];
    }

    /**
     * Utility function to measure the execution of a Closure
     *
     * @param string $label
     * @param string|null $collector
     * @return mixed
     */
    #[\ReturnTypeWillChange] public function measure($label, \Closure $closure, $collector = null)
    {
        $name = spl_object_hash($closure);
        $this->startMeasure($name, $label, $collector);
        $result = $closure();
        $params = is_array($result) ? $result : [];
        $this->stopMeasure($name, $params);
        return $result;
    }

    /**
     * Returns an array of all measures
     *
     * @return array
     */
    #[\ReturnTypeWillChange] public function getMeasures()
    {
        return $this->measures;
    }

    /**
     * Returns the request start time
     *
     * @return float
     */
    #[\ReturnTypeWillChange] public function getRequestStartTime()
    {
        return $this->requestStartTime;
    }

    /**
     * Returns the request end time
     *
     * @return float
     */
    #[\ReturnTypeWillChange] public function getRequestEndTime()
    {
        return $this->requestEndTime;
    }

    /**
     * Returns the duration of a request
     *
     * @return float
     */
    #[\ReturnTypeWillChange] public function getRequestDuration()
    {
        if ($this->requestEndTime !== null) {
            return $this->requestEndTime - $this->requestStartTime;
        }

        return microtime(true) - $this->requestStartTime;
    }

    /**
     * @throws DebugBarException
     */
    #[\ReturnTypeWillChange] public function collect(): array
    {
        $this->requestEndTime = microtime(true);
        foreach (array_keys($this->startedMeasures) as $name) {
            $this->stopMeasure($name);
        }

        usort($this->measures, fn($a, $b): int => $a['start'] <=> $b['start']);

        return [
            'start' => $this->requestStartTime,
            'end' => $this->requestEndTime,
            'duration' => $this->getRequestDuration(),
            'duration_str' => $this->getDataFormatter()->formatDuration($this->getRequestDuration()),
            'measures' => array_values($this->measures)
        ];
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'time';
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            "time" => [
                "icon" => "clock-o",
                "tooltip" => "Request Duration",
                "map" => "time.duration_str",
                "default" => "'0ms'"
            ],
            "timeline" => [
                "icon" => "tasks",
                "widget" => "PhpDebugBar.Widgets.TimelineWidget",
                "map" => "time",
                "default" => "{}"
            ]
        ];
    }
}
