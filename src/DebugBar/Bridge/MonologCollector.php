<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Bridge;

use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\MessagesAggregateInterface;
use DebugBar\DataCollector\Renderable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * A monolog handler as well as a data collector
 *
 * https://github.com/Seldaek/monolog
 *
 * <code>
 * $debugbar->addCollector(new MonologCollector($logger));
 * </code>
 */
class MonologCollector extends AbstractProcessingHandler implements DataCollectorInterface, Renderable, MessagesAggregateInterface
{
    protected $records = [];

    /**
     * @param int $level
     * @param boolean $bubble
     * @param string $name
     */
    #[\ReturnTypeWillChange] public function __construct(?Logger $logger = null, $level = Logger::DEBUG, $bubble = true, protected $name = 'monolog')
    {
        parent::__construct($level, $bubble);
        if ($logger instanceof Logger) {
            $this->addLogger($logger);
        }
    }

    /**
     * Adds logger which messages you want to log
     */
    #[\ReturnTypeWillChange] public function addLogger(Logger $logger): void
    {
        $logger->pushHandler($this);
    }

    /**
     * @param array|\Monolog\LogRecord $record
     */
    protected function write($record): void
    {
        $this->records[] = [
            'message' => $record['formatted'],
            'is_string' => true,
            'label' => strtolower((string) $record['level_name']),
            'time' => $record['datetime']->format('U')
        ];
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function getMessages()
    {
        return $this->records;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function collect()
    {
        return [
            'count' => count($this->records),
            'records' => $this->records
        ];
    }

    /**
     * @return string
     */
    #[\ReturnTypeWillChange] public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function getWidgets()
    {
        $name = $this->getName();
        return [
            $name => [
                "icon" => "suitcase",
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => $name . '.records',
                "default" => "[]"
            ],
            $name . ':badge' => [
                "map" => $name . '.count',
                "default" => "null"
            ]
        ];
    }
}
