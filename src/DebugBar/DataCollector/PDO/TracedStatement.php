<?php

namespace DebugBar\DataCollector\PDO;

/**
 * Holds information about a statement
 */
class TracedStatement
{
    protected $rowCount;

    protected array $parameters;

    protected $startTime;

    protected $endTime;

    protected $duration;

    protected $startMemory;

    protected $endMemory;

    protected $memoryDelta;

    protected $exception;

    /**
     * @param string $preparedId
     */
    #[\ReturnTypeWillChange] public function __construct(protected string $sql, array $params = [], public $preparedId = null)
    {
        $this->parameters = $this->checkParameters($params);
    }

    #[\ReturnTypeWillChange] public function start($startTime = null, $startMemory = null) : void
    {
        $this->startTime = $startTime ?: microtime(true);
        $this->startMemory = $startMemory ?: memory_get_usage(false);
    }

    /**
     * @param \Exception|null $exception
     */
    #[\ReturnTypeWillChange] public function end(?\Exception $exception = null, int $rowCount = 0, ?float $endTime = null, ?int $endMemory = null) : void
    {
        $this->endTime = $endTime ?: microtime(true);
        $this->duration = $this->endTime - $this->startTime;
        $this->endMemory = $endMemory ?: memory_get_usage(false);
        $this->memoryDelta = $this->endMemory - $this->startMemory;
        $this->exception = $exception;
        $this->rowCount = $rowCount;
    }

    /**
     * Check parameters for illegal (non UTF-8) strings, like Binary data.
     */
    #[\ReturnTypeWillChange] public function checkParameters(array $params) : array
    {
        foreach ($params as &$param) {
            if (!mb_check_encoding($param ?? '', 'UTF-8')) {
                $param = '[BINARY DATA]';
            }
        }

        return $params;
    }

    /**
     * Returns the SQL string used for the query, without filled parameters
     */
    #[\ReturnTypeWillChange] public function getSql() : string
    {
        return $this->sql;
    }

    /**
     * Returns the SQL string with any parameters used embedded
     */
    #[\ReturnTypeWillChange] public function getSqlWithParams(string $quotationChar = '<>') : string
    {
        if (($l = strlen($quotationChar)) > 1) {
            $quoteLeft = substr($quotationChar, 0, $l / 2);
            $quoteRight = substr($quotationChar, $l / 2);
        } else {
            $quoteLeft = $quotationChar;
            $quoteRight = $quotationChar;
        }

        $sql = $this->sql;

        $cleanBackRefCharMap = ['%' => '%%', '$' => '$%', '\\' => '\\%'];

        foreach ($this->parameters as $k => $v) {

            $backRefSafeV = strtr($v, $cleanBackRefCharMap);

            $v = $quoteLeft . $backRefSafeV . $quoteRight;

            if (is_numeric($k)) {
                $marker = "\?";
            } else {
                $marker = (preg_match("/^:/", (string) $k)) ? $k : ":" . $k;
            }

            $matchRule = sprintf('/(%s(?!\w))(?=(?:[^%s]|[%s][^%s]*[%s])*$)/', $marker, $quotationChar, $quotationChar, $quotationChar, $quotationChar);
            $count = mb_substr_count((string) $sql, (string) $k);
            if ($count < 1) {
                $count = mb_substr_count((string) $sql, $matchRule);
            }

            for ($i = 0; $i <= $count; $i++) {
                $sql = preg_replace($matchRule, $v, (string) $sql, 1);
            }
        }

        return strtr($sql, array_flip($cleanBackRefCharMap));
    }

    /**
     * Returns the number of rows affected/returned
     */
    #[\ReturnTypeWillChange] public function getRowCount() : int
    {
        return $this->rowCount;
    }

    /**
     * Returns an array of parameters used with the query
     */
    #[\ReturnTypeWillChange] public function getParameters() : array
    {
        $params = [];
        foreach ($this->parameters as $name => $param) {
            $params[$name] = htmlentities((string) $param?:"", ENT_QUOTES, 'UTF-8', false);
        }

        return $params;
    }

    /**
     * Returns the prepared statement id
     */
    #[\ReturnTypeWillChange] public function getPreparedId() : string
    {
        return $this->preparedId;
    }

    /**
     * Checks if this is a prepared statement
     */
    #[\ReturnTypeWillChange] public function isPrepared() : bool
    {
        return $this->preparedId !== null;
    }

    #[\ReturnTypeWillChange] public function getStartTime() : float
    {
        return $this->startTime;
    }

    #[\ReturnTypeWillChange] public function getEndTime() : float
    {
        return $this->endTime;
    }

    /**
     * Returns the duration in seconds + microseconds of the execution
     */
    #[\ReturnTypeWillChange] public function getDuration() : float
    {
        return $this->duration;
    }

    #[\ReturnTypeWillChange] public function getStartMemory() : int
    {
        return $this->startMemory;
    }

    #[\ReturnTypeWillChange] public function getEndMemory() : int
    {
        return $this->endMemory;
    }

    /**
     * Returns the memory usage during the execution
     */
    #[\ReturnTypeWillChange] public function getMemoryUsage() : int
    {
        return $this->memoryDelta;
    }

    /**
     * Checks if the statement was successful
     */
    #[\ReturnTypeWillChange] public function isSuccess() : bool
    {
        return $this->exception === null;
    }

    /**
     * Returns the exception triggered
     */
    #[\ReturnTypeWillChange] public function getException() : \Exception
	{
        return $this->exception;
    }

    /**
     * Returns the exception's code
     *
     * @return int|string
     */
    #[\ReturnTypeWillChange] public function getErrorCode()
    {
        return $this->exception !== null ? $this->exception->getCode() : 0;
    }

    /**
     * Returns the exception's message
     */
    #[\ReturnTypeWillChange] public function getErrorMessage() : string
    {
        return $this->exception !== null ? $this->exception->getMessage() : '';
    }
}
