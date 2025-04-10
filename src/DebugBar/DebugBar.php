<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar;

use ArrayAccess;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\Storage\StorageInterface;

/**
 * Main DebugBar object
 *
 * Manages data collectors. DebugBar provides an array-like access
 * to collectors by name.
 *
 * <code>
 *     $debugbar = new DebugBar();
 *     $debugbar->addCollector(new DataCollector\MessagesCollector());
 *     $debugbar['messages']->addMessage("foobar");
 * </code>
 */
class DebugBar implements ArrayAccess
{
    public static $useOpenHandlerWhenSendingDataHeaders = false;

    protected $collectors = [];

    protected $data;

    protected $jsRenderer;

    protected $requestIdGenerator;

    protected $requestId;

    protected $storage;

    protected $httpDriver;

    protected $stackSessionNamespace = 'PHPDEBUGBAR_STACK_DATA';

    protected $stackAlwaysUseSessionStorage = false;

    /**
     * Adds a data collector
     *
     *
     * @throws DebugBarException
     * @return $this
     */
    #[\ReturnTypeWillChange] public function addCollector(DataCollectorInterface $collector): static
    {
        if ($collector->getName() === '__meta') {
            throw new DebugBarException("'__meta' is a reserved name and cannot be used as a collector name");
        }

        if (isset($this->collectors[$collector->getName()])) {
            throw new DebugBarException(sprintf("'%s' is already a registered collector", $collector->getName()));
        }

        $this->collectors[$collector->getName()] = $collector;
        return $this;
    }

    /**
     * Checks if a data collector has been added
     *
     * @param string $name
     */
    #[\ReturnTypeWillChange] public function hasCollector($name): bool
    {
        return isset($this->collectors[$name]);
    }

    /**
     * Returns a data collector
     *
     * @param string $name
     * @return DataCollectorInterface
     * @throws DebugBarException
     */
    #[\ReturnTypeWillChange] public function getCollector($name)
    {
        if (!isset($this->collectors[$name])) {
            throw new DebugBarException(sprintf("'%s' is not a registered collector", $name));
        }

        return $this->collectors[$name];
    }

    /**
     * Returns an array of all data collectors
     *
     * @return array[DataCollectorInterface]
     */
    #[\ReturnTypeWillChange] public function getCollectors()
    {
        return $this->collectors;
    }

    /**
     * Sets the request id generator
     *
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setRequestIdGenerator(RequestIdGeneratorInterface $generator): static
    {
        $this->requestIdGenerator = $generator;
        return $this;
    }

    /**
     * @return RequestIdGeneratorInterface
     */
    #[\ReturnTypeWillChange] public function getRequestIdGenerator()
    {
        if ($this->requestIdGenerator === null) {
            $this->requestIdGenerator = new RequestIdGenerator();
        }

        return $this->requestIdGenerator;
    }

    /**
     * Returns the id of the current request
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getCurrentRequestId()
    {
        if ($this->requestId === null) {
            $this->requestId = $this->getRequestIdGenerator()->generate();
        }

        return $this->requestId;
    }

    /**
     * Sets the storage backend to use to store the collected data
     *
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setStorage(?StorageInterface $storage = null): static
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * @return StorageInterface
     */
    #[\ReturnTypeWillChange] public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Checks if the data will be persisted
     */
    #[\ReturnTypeWillChange] public function isDataPersisted(): bool
    {
        return $this->storage !== null;
    }

    /**
     * Sets the HTTP driver
     *
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setHttpDriver(HttpDriverInterface $driver): static
    {
        $this->httpDriver = $driver;
        return $this;
    }

    /**
     * Returns the HTTP driver
     *
     * If no http driver where defined, a PhpHttpDriver is automatically created
     *
     * @return HttpDriverInterface
     */
    #[\ReturnTypeWillChange] public function getHttpDriver()
    {
        if ($this->httpDriver === null) {
            $this->httpDriver = new PhpHttpDriver();
        }

        return $this->httpDriver;
    }

    /**
     * Collects the data from the collectors
     *
     * @return array
     */
    #[\ReturnTypeWillChange] public function collect()
    {
        if (PHP_SAPI === 'cli') {
            $ip = gethostname();
            $ip = $ip ? gethostbyname($ip) : '127.0.0.1';

            $request_variables = [
                'method' => 'CLI',
                'uri' => isset($_SERVER['SCRIPT_FILENAME']) ? realpath($_SERVER['SCRIPT_FILENAME']) : null,
                'ip' => $ip
            ];
        } else {
            $request_variables = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ];
        }

        $this->data = [
            '__meta' => array_merge(
                [
                    'id' => $this->getCurrentRequestId(),
                    'datetime' => date('Y-m-d H:i:s'),
                    'utime' => microtime(true)
                ],
                $request_variables
            )
        ];

        foreach ($this->collectors as $name => $collector) {
            $this->data[$name] = $collector->collect();
        }

        // Remove all invalid (non UTF-8) characters
        array_walk_recursive($this->data, function (&$item): void {
                if (is_string($item) && !mb_check_encoding($item, 'UTF-8')) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            });

        if ($this->storage !== null) {
            $this->storage->save($this->getCurrentRequestId(), $this->data);
        }

        return $this->data;
    }

    /**
     * Returns collected data
     *
     * Will collect the data if none have been collected yet
     *
     * @return array
     */
    #[\ReturnTypeWillChange] public function getData()
    {
        if ($this->data === null) {
            $this->collect();
        }

        return $this->data;
    }

    /**
     * Returns an array of HTTP headers containing the data
     *
     * @param integer $maxHeaderLength
     */
    #[\ReturnTypeWillChange] public function getDataAsHeaders(string $headerName = 'phpdebugbar', $maxHeaderLength = 4096, $maxTotalHeaderLength = 250000): array
    {
        $data = rawurlencode(json_encode([
            'id' => $this->getCurrentRequestId(),
            'data' => $this->getData()
        ]));

        if (strlen($data) > $maxTotalHeaderLength) {
            $data = rawurlencode(json_encode([
                'error' => 'Maximum header size exceeded'
            ]));
        }

        $chunks = [];

        while (strlen($data) > $maxHeaderLength) {
            $chunks[] = substr($data, 0, $maxHeaderLength);
            $data = substr($data, $maxHeaderLength);
        }

        $chunks[] = $data;

        $headers = [];
        for ($i = 0, $c = count($chunks); $i < $c; $i++) {
            $name = $headerName . ($i > 0 ? '-' . $i : '');
            $headers[$name] = $chunks[$i];
        }

        return $headers;
    }

    /**
     * Sends the data through the HTTP headers
     *
     * @param bool $useOpenHandler
     * @param integer $maxHeaderLength
     * @return $this
     */
    #[\ReturnTypeWillChange] public function sendDataInHeaders($useOpenHandler = null, string $headerName = 'phpdebugbar', $maxHeaderLength = 4096): static
    {
        if ($useOpenHandler === null) {
            $useOpenHandler = self::$useOpenHandlerWhenSendingDataHeaders;
        }

        if ($useOpenHandler && $this->storage !== null) {
            $this->getData();
            $headerName .= '-id';
            $headers = [$headerName => $this->getCurrentRequestId()];
        } else {
            $headers = $this->getDataAsHeaders($headerName, $maxHeaderLength);
        }

        $this->getHttpDriver()->setHeaders($headers);
        return $this;
    }

    /**
     * Stacks the data in the session for later rendering
     */
    #[\ReturnTypeWillChange] public function stackData(): static
    {
        $http = $this->initStackSession();

        $data = null;
        if (!$this->isDataPersisted() || $this->stackAlwaysUseSessionStorage) {
            $data = $this->getData();
        } elseif ($this->data === null) {
            $this->collect();
        }

        $stack = $http->getSessionValue($this->stackSessionNamespace);
        $stack[$this->getCurrentRequestId()] = $data;
        $http->setSessionValue($this->stackSessionNamespace, $stack);
        return $this;
    }

    /**
     * Checks if there is stacked data in the session
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function hasStackedData()
    {
        try {
            $http = $this->initStackSession();
        } catch (DebugBarException) {
            return false;
        }

        return count($http->getSessionValue($this->stackSessionNamespace)) > 0;
    }

    /**
     * Returns the data stacked in the session
     *
     * @param boolean $delete Whether to delete the data in the session
     * @return array
     */
    #[\ReturnTypeWillChange] public function getStackedData($delete = true)
    {
        $http = $this->initStackSession();
        $stackedData = $http->getSessionValue($this->stackSessionNamespace);
        if ($delete) {
            $http->deleteSessionValue($this->stackSessionNamespace);
        }

        $datasets = [];
        if ($this->isDataPersisted() && !$this->stackAlwaysUseSessionStorage) {
            foreach ($stackedData as $id => $data) {
                $datasets[$id] = $this->getStorage()->get($id);
            }
        } else {
            $datasets = $stackedData;
        }

        return $datasets;
    }

    /**
     * Sets the key to use in the $_SESSION array
     *
     * @param string $ns
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setStackDataSessionNamespace($ns): static
    {
        $this->stackSessionNamespace = $ns;
        return $this;
    }

    /**
     * Returns the key used in the $_SESSION array
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getStackDataSessionNamespace()
    {
        return $this->stackSessionNamespace;
    }

    /**
     * Sets whether to only use the session to store stacked data even
     * if a storage is enabled
     *
     * @param boolean $enabled
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setStackAlwaysUseSessionStorage($enabled = true): static
    {
        $this->stackAlwaysUseSessionStorage = $enabled;
        return $this;
    }

    /**
     * Checks if the session is always used to store stacked data
     * even if a storage is enabled
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function isStackAlwaysUseSessionStorage()
    {
        return $this->stackAlwaysUseSessionStorage;
    }

    /**
     * Initializes the session for stacked data
     * @return HttpDriverInterface
     * @throws DebugBarException
     */
    protected function initStackSession()
    {
        $http = $this->getHttpDriver();
        if (!$http->isSessionStarted()) {
            throw new DebugBarException("Session must be started before using stack data in the debug bar");
        }

        if (!$http->hasSessionValue($this->stackSessionNamespace)) {
            $http->setSessionValue($this->stackSessionNamespace, []);
        }

        return $http;
    }

    /**
     * Returns a JavascriptRenderer for this instance
     * @param string $baseUrl
     * @param string $basePath
     * @return JavascriptRenderer
     */
    #[\ReturnTypeWillChange] public function getJavascriptRenderer($baseUrl = null, $basePath = null)
    {
        if ($this->jsRenderer === null) {
            $this->jsRenderer = new JavascriptRenderer($this, $baseUrl, $basePath);
        }

        return $this->jsRenderer;
    }

    // --------------------------------------------
    // ArrayAccess implementation

    #[\ReturnTypeWillChange] public function offsetSet($key, $value)
    {
        throw new DebugBarException("DebugBar[] is read-only");
    }

    #[\ReturnTypeWillChange] public function offsetGet($key)
    {
        return $this->getCollector($key);
    }

    #[\ReturnTypeWillChange] public function offsetExists($key)
    {
        return $this->hasCollector($key);
    }

    #[\ReturnTypeWillChange] public function offsetUnset($key)
    {
        throw new DebugBarException("DebugBar[] is read-only");
    }
}
