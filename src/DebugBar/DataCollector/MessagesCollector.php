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

use Psr\Log\AbstractLogger;
use DebugBar\DataFormatter\DataFormatterInterface;
use DebugBar\DataFormatter\DebugBarVarDumper;

/**
 * Provides a way to log messages
 */
class MessagesCollector extends AbstractLogger implements DataCollectorInterface, MessagesAggregateInterface, Renderable, AssetProvider
{
    protected $messages = [];

    protected $aggregates = [];

    protected $dataFormater;

    protected $varDumper;

    // The HTML var dumper requires debug bar users to support the new inline assets, which not all
    // may support yet - so return false by default for now.
    protected $useHtmlVarDumper = false;

    /**
     * @param string $name
     */
    #[\ReturnTypeWillChange]
    public function __construct(protected $name = 'messages')
    {
    }

    /**
     * Sets the data formater instance used by this collector
     *
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setDataFormatter(DataFormatterInterface $formater): static
    {
        $this->dataFormater = $formater;
        return $this;
    }

    /**
     * @return DataFormatterInterface
     */
    #[\ReturnTypeWillChange] public function getDataFormatter()
    {
        if ($this->dataFormater === null) {
            $this->dataFormater = DataCollector::getDefaultDataFormatter();
        }

        return $this->dataFormater;
    }

    /**
     * Sets the variable dumper instance used by this collector
     *
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setVarDumper(DebugBarVarDumper $varDumper): static
    {
        $this->varDumper = $varDumper;
        return $this;
    }

    /**
     * Gets the variable dumper instance used by this collector
     *
     * @return DebugBarVarDumper
     */
    #[\ReturnTypeWillChange] public function getVarDumper()
    {
        if ($this->varDumper === null) {
            $this->varDumper = DataCollector::getDefaultVarDumper();
        }

        return $this->varDumper;
    }

    /**
     * Sets a flag indicating whether the Symfony HtmlDumper will be used to dump variables for
     * rich variable rendering.  Be sure to set this flag before logging any messages for the
     * first time.
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

    /**
     * Adds a message
     *
     * A message can be anything from an object to a string
     *
     * @param mixed $message
     * @param string $label
     */
    #[\ReturnTypeWillChange] public function addMessage($message, $label = 'info', $isString = true): void
    {
        $messageText = $message;
        $messageHtml = null;
        if (!is_string($message)) {
            // Send both text and HTML representations; the text version is used for searches
            $messageText = $this->getDataFormatter()->formatVar($message);
            if ($this->isHtmlVarDumperUsed()) {
                $messageHtml = $this->getVarDumper()->renderVar($message);
            }

            $isString = false;
        }

        $this->messages[] = [
            'message' => $messageText,
            'message_html' => $messageHtml,
            'is_string' => $isString,
            'label' => $label,
            'time' => microtime(true)
        ];
    }

    /**
     * Aggregates messages from other collectors
     */
    #[\ReturnTypeWillChange] public function aggregate(MessagesAggregateInterface $messages): void
    {
        $this->aggregates[] = $messages;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function getMessages()
    {
        $messages = $this->messages;
        foreach ($this->aggregates as $collector) {
            $msgs = array_map(function (array $m) use ($collector) {
                $m['collector'] = $collector->getName();
                return $m;
            }, $collector->getMessages());
            $messages = array_merge($messages, $msgs);
        }

        // sort messages by their timestamp
        usort($messages, fn($a, $b): int => $a['time'] <=> $b['time']);

        return $messages;
    }

    /**
     * @param $level
     * @param $message
     */
    #[\ReturnTypeWillChange] public function log($level, $message, array $context = []): void
    {
        // For string messages, interpolate the context following PSR-3
        if (is_string($message)) {
            $message = $this->interpolate($message, $context);
        }

        $this->addMessage($message, $level);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param $message
     */
    public function interpolate($message, array $context = []): string
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // check that the value can be cast to string
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * Deletes all messages
     */
    #[\ReturnTypeWillChange] public function clear(): void
    {
        $this->messages = [];
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        $messages = $this->getMessages();
        return [
            'count' => count($messages),
            'messages' => $messages
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
    #[\ReturnTypeWillChange] public function getAssets() {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : [];
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        $name = $this->getName();
        return [
            $name => [
                'icon' => 'list-alt',
                "widget" => "PhpDebugBar.Widgets.MessagesWidget",
                "map" => $name . '.messages',
                "default" => "[]"
            ],
            $name . ':badge' => [
                "map" => $name . '.count',
                "default" => "null"
            ]
        ];
    }
}
