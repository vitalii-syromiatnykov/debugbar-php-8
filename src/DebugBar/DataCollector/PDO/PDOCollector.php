<?php

namespace DebugBar\DataCollector\PDO;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\TimeDataCollector;

/**
 * Collects data about SQL statements executed with PDO
 */
class PDOCollector extends DataCollector implements Renderable, AssetProvider
{
    protected $connections = [];

    protected $renderSqlWithParams = false;

    protected $sqlQuotationChar = '<>';

    #[\ReturnTypeWillChange] public function __construct(?\PDO $pdo = null, protected ?TimeDataCollector $timeCollector = null)
    {
        if ($pdo instanceof \PDO) {
            $this->addConnection($pdo, 'default');
        }
    }

    /**
     * Renders the SQL of traced statements with params embeded
     *
     * @param boolean $enabled
     */
    #[\ReturnTypeWillChange] public function setRenderSqlWithParams($enabled = true, $quotationChar = '<>'): void
    {
        $this->renderSqlWithParams = $enabled;
        $this->sqlQuotationChar = $quotationChar;
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange] public function isSqlRenderedWithParams()
    {
        return $this->renderSqlWithParams;
    }

    /**
     * @return string
     */
    #[\ReturnTypeWillChange] public function getSqlQuotationChar()
    {
        return $this->sqlQuotationChar;
    }

    /**
     * Adds a new PDO instance to be collector
     *
     * @param TraceablePDO $pdo
     * @param string $name Optional connection name
     */
    #[\ReturnTypeWillChange] public function addConnection(\PDO $pdo, $name = null): void
    {
        if ($name === null) {
            $name = spl_object_hash($pdo);
        }

        if (!($pdo instanceof TraceablePDO)) {
            $pdo = new TraceablePDO($pdo);
        }

        $this->connections[$name] = $pdo;
    }

    /**
     * Returns PDO instances to be collected
     *
     * @return array
     */
    #[\ReturnTypeWillChange] public function getConnections()
    {
        return $this->connections;
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        $data = [
            'nb_statements' => 0,
            'nb_failed_statements' => 0,
            'accumulated_duration' => 0,
            'memory_usage' => 0,
            'peak_memory_usage' => 0,
            'statements' => []
        ];

        foreach ($this->connections as $name => $pdo) {
            $pdodata = $this->collectPDO($pdo, $this->timeCollector, $name);
            $data['nb_statements'] += $pdodata['nb_statements'];
            $data['nb_failed_statements'] += $pdodata['nb_failed_statements'];
            $data['accumulated_duration'] += $pdodata['accumulated_duration'];
            $data['memory_usage'] += $pdodata['memory_usage'];
            $data['peak_memory_usage'] = max($data['peak_memory_usage'], $pdodata['peak_memory_usage']);
            $data['statements'] = array_merge($data['statements'],
                array_map(function (array $s) use ($name) { $s['connection'] = $name; return $s; }, $pdodata['statements']));
        }

        $data['accumulated_duration_str'] = $this->getDataFormatter()->formatDuration($data['accumulated_duration']);
        $data['memory_usage_str'] = $this->getDataFormatter()->formatBytes($data['memory_usage']);
        $data['peak_memory_usage_str'] = $this->getDataFormatter()->formatBytes($data['peak_memory_usage']);

        return $data;
    }

    /**
     * Collects data from a single TraceablePDO instance
     *
     * @param string|null $connectionName the pdo connection (eg default | read | write)
     */
    protected function collectPDO(TraceablePDO $pdo, ?TimeDataCollector $timeCollector = null, $connectionName = null): array
    {
        $connectionName = empty($connectionName) || $connectionName == 'default' ? 'pdo' : 'pdo ' . $connectionName;

        $stmts = [];
        foreach ($pdo->getExecutedStatements() as $stmt) {
            $stmts[] = [
                'sql' => $this->renderSqlWithParams ? $stmt->getSqlWithParams($this->sqlQuotationChar) : $stmt->getSql(),
                'row_count' => $stmt->getRowCount(),
                'stmt_id' => $stmt->getPreparedId(),
                'prepared_stmt' => $stmt->getSql(),
                'params' => (object) $stmt->getParameters(),
                'duration' => $stmt->getDuration(),
                'duration_str' => $this->getDataFormatter()->formatDuration($stmt->getDuration()),
                'memory' => $stmt->getMemoryUsage(),
                'memory_str' => $this->getDataFormatter()->formatBytes($stmt->getMemoryUsage()),
                'end_memory' => $stmt->getEndMemory(),
                'end_memory_str' => $this->getDataFormatter()->formatBytes($stmt->getEndMemory()),
                'is_success' => $stmt->isSuccess(),
                'error_code' => $stmt->getErrorCode(),
                'error_message' => $stmt->getErrorMessage()
            ];
            if ($timeCollector instanceof TimeDataCollector) {
                $timeCollector->addMeasure($stmt->getSql(), $stmt->getStartTime(), $stmt->getEndTime(), [], $connectionName);
            }
        }

        return [
            'nb_statements' => count($stmts),
            'nb_failed_statements' => count($pdo->getFailedExecutedStatements()),
            'accumulated_duration' => $pdo->getAccumulatedStatementsDuration(),
            'accumulated_duration_str' => $this->getDataFormatter()->formatDuration($pdo->getAccumulatedStatementsDuration()),
            'memory_usage' => $pdo->getMemoryUsage(),
            'memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'peak_memory_usage' => $pdo->getPeakMemoryUsage(),
            'peak_memory_usage_str' => $this->getDataFormatter()->formatBytes($pdo->getPeakMemoryUsage()),
            'statements' => $stmts
        ];
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'pdo';
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            "database" => [
                "icon" => "database",
                "widget" => "PhpDebugBar.Widgets.SQLQueriesWidget",
                "map" => "pdo",
                "default" => "[]"
            ],
            "database:badge" => [
                "map" => "pdo.nb_statements",
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
