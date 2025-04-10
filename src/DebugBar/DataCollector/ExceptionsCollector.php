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

use Exception;

/**
 * Collects info about exceptions
 */
class ExceptionsCollector extends DataCollector implements Renderable
{
    protected $exceptions = [];

    protected $chainExceptions = false;

    // The HTML var dumper requires debug bar users to support the new inline assets, which not all
    // may support yet - so return false by default for now.
    protected $useHtmlVarDumper = false;

    /**
     * Adds an exception to be profiled in the debug bar
     *
     * @deprecated in favor on addThrowable
     */
    #[\ReturnTypeWillChange] public function addException(Exception $e): void
    {
        $this->addThrowable($e);
    }

    /**
     * Adds a Throwable to be profiled in the debug bar
     *
     * @param \Throwable $e
     */
    #[\ReturnTypeWillChange] public function addThrowable($e): void
    {
        $this->exceptions[] = $e;
        if ($this->chainExceptions && $previous = $e->getPrevious()) {
            $this->addThrowable($previous);
        }
    }

    /**
     * Configure whether or not all chained exceptions should be shown.
     *
     * @param bool $chainExceptions
     */
    #[\ReturnTypeWillChange] public function setChainExceptions($chainExceptions = true): void
    {
        $this->chainExceptions = $chainExceptions;
    }

    /**
     * Returns the list of exceptions being profiled
     *
     * @return array[\Throwable]
     */
    #[\ReturnTypeWillChange] public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Sets a flag indicating whether the Symfony HtmlDumper will be used to dump variables for
     * rich variable rendering.
     *
     * @param bool $value
     * @return $this
     */
    #[\ReturnTypeWillChange] public function useHtmlVarDumper($value = true): static
    {
        $this->useHtmlVarDumper = $value;
        return $this;
    }

    /**
     * Indicates whether the Symfony HtmlDumper will be used to dump variables for rich variable
     * rendering.
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange] public function isHtmlVarDumperUsed()
    {
        return $this->useHtmlVarDumper;
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        return [
            'count' => count($this->exceptions),
            'exceptions' => array_map($this->formatThrowableData(...), $this->exceptions)
        ];
    }

    /**
     * Returns exception data as an array
     *
     * @return array
     * @deprecated in favor on formatThrowableData
     */
    #[\ReturnTypeWillChange] public function formatExceptionData(Exception $e)
    {
        return $this->formatThrowableData($e);
    }

    /**
     * Returns Throwable data as an array
     *
     * @param \Throwable $e
     */
    #[\ReturnTypeWillChange] public function formatThrowableData($e): array
    {
        $filePath = $e->getFile();
        if ($filePath && file_exists($filePath)) {
            $lines = file($filePath);
            $start = $e->getLine() - 4;
            $lines = array_slice($lines, max(0, $start), 7);
        } else {
            $lines = [sprintf('Cannot open the file (%s) in which the exception occurred ', $filePath)];
        }

        $traceHtml = null;
        if ($this->isHtmlVarDumperUsed()) {
            $traceHtml = $this->getVarDumper()->renderVar($e->getTrace());
        }

        return [
            'type' => $e::class,
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $filePath,
            'line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString(),
            'stack_trace_html' => $traceHtml,
            'surrounding_lines' => $lines,
            'xdebug_link' => $this->getXdebugLink($filePath, $e->getLine())
        ];
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'exceptions';
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            'exceptions' => [
                'icon' => 'bug',
                'widget' => 'PhpDebugBar.Widgets.ExceptionsWidget',
                'map' => 'exceptions.exceptions',
                'default' => '[]'
            ],
            'exceptions:badge' => [
                'map' => 'exceptions.count',
                'default' => 'null'
            ]
        ];
    }
}
