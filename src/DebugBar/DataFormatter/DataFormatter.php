<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataFormatter;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class DataFormatter implements DataFormatterInterface
{
    /**
     * @var VarCloner
     */
    public $cloner;

    /**
     * @var CliDumper
     */
    public $dumper;

    /**
     * DataFormatter constructor.
     */
    #[\ReturnTypeWillChange] public function __construct()
    {
        $this->cloner = new VarCloner();
        $this->dumper = new CliDumper();
    }

    /**
     * @param $data
     */
    #[\ReturnTypeWillChange] public function formatVar($data): string
    {
        $output = '';

        $this->dumper->dump(
            $this->cloner->cloneVar($data),
            function (string $line, $depth) use (&$output): void {
                // A negative depth means "end of dump"
                if ($depth >= 0) {
                    // Adds a two spaces indentation to the line
                    $output .= str_repeat('  ', $depth).$line."\n";
                }
            }
        );

        return trim($output);
    }

    /**
     * @param float $seconds
     */
    #[\ReturnTypeWillChange] public function formatDuration($seconds): string
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 0.1) {
            return round($seconds * 1000, 2) . 'ms';
        } elseif ($seconds < 1) {
            return round($seconds * 1000) . 'ms';
        }

        return round($seconds, 2) . 's';
    }

    /**
     * @param string $size
     * @param int $precision
     */
    #[\ReturnTypeWillChange] public function formatBytes($size, $precision = 2): string
    {
        if ($size === 0 || $size === null) {
            return "0B";
        }

        $sign = $size < 0 ? '-' : '';
        $size = abs($size);

        $base = log($size) / log(1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return $sign . round(1024 ** ($base - floor($base)), $precision) . $suffixes[(int) floor($base)];
    }
}
