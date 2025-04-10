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

use BasicLogger;
use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Propel;
use PropelConfiguration;
use PropelPDO;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * A Propel logger which acts as a data collector
 *
 * http://propelorm.org/
 *
 * Will log queries and display them using the SQLQueries widget.
 * You can provide a LoggerInterface object to forward non-query related message to.
 *
 * Example:
 * <code>
 * $debugbar->addCollector(new PropelCollector($debugbar['messages']));
 * PropelCollector::enablePropelProfiling();
 * </code>
 */
class PropelCollector extends DataCollector implements BasicLogger, Renderable, AssetProvider
{
    /**
     * @var false
     */
    public $logQueriesToLogger;

    protected $statements = [];

    protected $accumulatedTime = 0;

    protected $peakMemory = 0;

    /**
     * Sets the needed configuration option in propel to enable query logging
     *
     * @param PropelConfiguration $config Apply profiling on a specific config
     */
    public static function enablePropelProfiling(?PropelConfiguration $config = null): void
    {
        if (!$config instanceof \PropelConfiguration) {
            $config = Propel::getConfiguration(PropelConfiguration::TYPE_OBJECT);
        }

        $config->setParameter('debugpdo.logging.details.method.enabled', true);
        $config->setParameter('debugpdo.logging.details.time.enabled', true);
        $config->setParameter('debugpdo.logging.details.mem.enabled', true);

        $allMethods = [
            'PropelPDO::__construct',       // logs connection opening
            'PropelPDO::__destruct',        // logs connection close
            'PropelPDO::exec',              // logs a query
            'PropelPDO::query',             // logs a query
            'PropelPDO::beginTransaction',  // logs a transaction begin
            'PropelPDO::commit',            // logs a transaction commit
            'PropelPDO::rollBack',          // logs a transaction rollBack (watch out for the capital 'B')
            'DebugPDOStatement::execute',   // logs a query from a prepared statement
        ];
        $config->setParameter('debugpdo.logging.methods', $allMethods, false);
    }

    /**
     * @param LoggerInterface $logger A logger to forward non-query log lines to
     * @param PropelPDO $conn Bound this collector to a connection only
     */
    #[\ReturnTypeWillChange] public function __construct(protected ?LoggerInterface $logger = null, ?PropelPDO $conn = null)
    {
        if ($conn instanceof \PropelPDO) {
            $conn->setLogger($this);
        } else {
            Propel::setLogger($this);
        }

        $this->logQueriesToLogger = false;
    }

    #[\ReturnTypeWillChange] public function setLogQueriesToLogger($enable = true): static
    {
        $this->logQueriesToLogger = $enable;
        return $this;
    }

    #[\ReturnTypeWillChange] public function isLogQueriesToLogger()
    {
        return $this->logQueriesToLogger;
    }

    #[\ReturnTypeWillChange] public function emergency($m): void
    {
        $this->log($m, Propel::LOG_EMERG);
    }

    #[\ReturnTypeWillChange] public function alert($m): void
    {
        $this->log($m, Propel::LOG_ALERT);
    }

    #[\ReturnTypeWillChange] public function crit($m): void
    {
        $this->log($m, Propel::LOG_CRIT);
    }

    #[\ReturnTypeWillChange] public function err($m): void
    {
        $this->log($m, Propel::LOG_ERR);
    }

    #[\ReturnTypeWillChange] public function warning($m): void
    {
        $this->log($m, Propel::LOG_WARNING);
    }

    #[\ReturnTypeWillChange] public function notice($m): void
    {
        $this->log($m, Propel::LOG_NOTICE);
    }

    #[\ReturnTypeWillChange] public function info($m): void
    {
        $this->log($m, Propel::LOG_INFO);
    }

    #[\ReturnTypeWillChange] public function debug($m): void
    {
        $this->log($m, Propel::LOG_DEBUG);
    }

    #[\ReturnTypeWillChange] public function log($message, $severity = null): void
    {
        if (str_contains((string) $message, 'DebugPDOStatement::execute')) {
            [$sql, $duration_str] = $this->parseAndLogSqlQuery($message);
            if (!$this->logQueriesToLogger) {
                return;
            }

            $message = sprintf('%s (%s)', $sql, $duration_str);
        }

        if ($this->logger !== null) {
            $this->logger->log($this->convertLogLevel($severity), $message);
        }
    }

    /**
     * Converts Propel log levels to PSR log levels
     *
     * @param int $level
     */
    protected function convertLogLevel($level): string
    {
        $map = [
            Propel::LOG_EMERG => LogLevel::EMERGENCY,
            Propel::LOG_ALERT => LogLevel::ALERT,
            Propel::LOG_CRIT => LogLevel::CRITICAL,
            Propel::LOG_ERR => LogLevel::ERROR,
            Propel::LOG_WARNING => LogLevel::WARNING,
            Propel::LOG_NOTICE => LogLevel::NOTICE,
            Propel::LOG_DEBUG => LogLevel::DEBUG
        ];
        return $map[$level];
    }

    /**
     * Parse a log line to extract query information
     *
     * @param string $message
     */
    protected function parseAndLogSqlQuery($message): array
    {
        $parts = explode('|', $message, 4);
        $sql = trim($parts[3]);

        $duration = 0;
        if (preg_match('/(\d+\.\d+)/', $parts[1], $matches)) {
            $duration = (float) $matches[1];
        }

        $memory = 0;
        if (preg_match('/(\d+\.\d+) ([A-Z]{1,2})/', $parts[2], $matches)) {
            $memory = (float) $matches[1];
            if ($matches[2] === 'KB') {
                $memory *= 1024;
            } elseif ($matches[2] === 'MB') {
                $memory *= 1024 * 1024;
            }
        }

        $this->statements[] = [
            'sql' => $sql,
            'is_success' => true,
            'duration' => $duration,
            'duration_str' => $this->formatDuration($duration),
            'memory' => $memory,
            'memory_str' => $this->formatBytes($memory)
        ];
        $this->accumulatedTime += $duration;
        $this->peakMemory = max($this->peakMemory, $memory);
        return [$sql, $this->formatDuration($duration)];
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        return [
            'nb_statements' => count($this->statements),
            'nb_failed_statements' => 0,
            'accumulated_duration' => $this->accumulatedTime,
            'accumulated_duration_str' => $this->formatDuration($this->accumulatedTime),
            'peak_memory_usage' => $this->peakMemory,
            'peak_memory_usage_str' => $this->formatBytes($this->peakMemory),
            'statements' => $this->statements
        ];
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'propel';
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            "propel" => [
                "icon" => "bolt",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "propel",
                "default" => "[]"
            ],
            "propel:badge" => [
                "map" => "propel.nb_statements",
                "default" => 0
            ]
        ];
    }

    #[\ReturnTypeWillChange] public function getAssets(): array
    {
        return [
            'css' => 'widgets/sqlqueries/widget.css',
            'js' => 'widgets/sqlqueries/widget.js'
        ];
    }
}
