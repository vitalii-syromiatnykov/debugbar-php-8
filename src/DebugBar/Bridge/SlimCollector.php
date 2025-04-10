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

use DebugBar\DataCollector\MessagesCollector;
use Psr\Log\LogLevel;
use Slim\Log;
use Slim\Slim;

/**
 * Collects messages from a Slim logger
 *
 * http://slimframework.com
 */
class SlimCollector extends MessagesCollector
{
    protected $originalLogWriter;

    #[\ReturnTypeWillChange] public function __construct(protected Slim $slim)
    {
        if ($log = $this->slim->getLog()) {
            $this->originalLogWriter = $log->getWriter();
            $log->setWriter($this);
            $log->setEnabled(true);
        }
    }

    #[\ReturnTypeWillChange] public function write($message, $level): void
    {
        if ($this->originalLogWriter) {
            $this->originalLogWriter->write($message, $level);
        }

        $this->addMessage($message, $this->getLevelName($level));
    }

    protected function getLevelName($level): string
    {
        $map = [
            Log::EMERGENCY => LogLevel::EMERGENCY,
            Log::ALERT => LogLevel::ALERT,
            Log::CRITICAL => LogLevel::CRITICAL,
            Log::ERROR => LogLevel::ERROR,
            Log::WARN => LogLevel::WARNING,
            Log::NOTICE => LogLevel::NOTICE,
            Log::INFO => LogLevel::INFO,
            Log::DEBUG => LogLevel::DEBUG
        ];
        return $map[$level];
    }

    #[\ReturnTypeWillChange]
    #[\Override] public function getName(): string
    {
        return 'slim';
    }
}
