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

use DebugBar\DataFormatter\DataFormatter;
use DebugBar\DataFormatter\DataFormatterInterface;
use DebugBar\DataFormatter\DebugBarVarDumper;

/**
 * Abstract class for data collectors
 */
abstract class DataCollector implements DataCollectorInterface
{
    private static DataFormatterInterface|DataFormatter|null $defaultDataFormatter = null;

    private static ?DebugBarVarDumper $defaultVarDumper = null;

    protected $dataFormater;

    protected $varDumper;

    protected $xdebugLinkTemplate = '';

    protected $xdebugShouldUseAjax = false;

    protected $xdebugReplacements = [];

    /**
     * Sets the default data formater instance used by all collectors subclassing this class
     */
    public static function setDefaultDataFormatter(DataFormatterInterface $formater): void
    {
        self::$defaultDataFormatter = $formater;
    }

    /**
     * Returns the default data formater
     *
     * @return DataFormatterInterface
     */
    public static function getDefaultDataFormatter()
    {
        if (self::$defaultDataFormatter === null) {
            self::$defaultDataFormatter = new DataFormatter();
        }

        return self::$defaultDataFormatter;
    }

    /**
     * Sets the data formater instance used by this collector
     *
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setDataFormatter(DataFormatterInterface $formater)
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
            $this->dataFormater = self::getDefaultDataFormatter();
        }

        return $this->dataFormater;
    }

    /**
     * Get an Xdebug Link to a file
     *
     * @param string $file
     * @param int    $line
     *
     * @return array {
     * @var string   $url
     * @var bool     $ajax should be used to open the url instead of a normal links
     * }
     */
    #[\ReturnTypeWillChange] public function getXdebugLink($file, $line = 1)
    {
        if (count($this->xdebugReplacements) > 0) {
            $file = strtr($file, $this->xdebugReplacements);
        }

        $url = strtr($this->getXdebugLinkTemplate(), ['%f' => $file, '%l' => $line]);
        if ($url !== '' && $url !== '0') {
            return ['url' => $url, 'ajax' => $this->getXdebugShouldUseAjax()];
        }
        return null;
    }

    /**
     * Sets the default variable dumper used by all collectors subclassing this class
     */
    public static function setDefaultVarDumper(DebugBarVarDumper $varDumper): void
    {
        self::$defaultVarDumper = $varDumper;
    }

    /**
     * Returns the default variable dumper
     *
     * @return DebugBarVarDumper
     */
    public static function getDefaultVarDumper()
    {
        if (self::$defaultVarDumper === null) {
            self::$defaultVarDumper = new DebugBarVarDumper();
        }

        return self::$defaultVarDumper;
    }

    /**
     * Sets the variable dumper instance used by this collector
     *
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setVarDumper(DebugBarVarDumper $varDumper)
    {
        $this->varDumper = $varDumper;
        return $this;
    }

    /**
     * Gets the variable dumper instance used by this collector; note that collectors using this
     * instance need to be sure to return the static assets provided by the variable dumper.
     *
     * @return DebugBarVarDumper
     */
    #[\ReturnTypeWillChange] public function getVarDumper()
    {
        if ($this->varDumper === null) {
            $this->varDumper = self::getDefaultVarDumper();
        }

        return $this->varDumper;
    }

    /**
     * @deprecated
     */
    #[\ReturnTypeWillChange] public function formatVar($var)
    {
        return $this->getDataFormatter()->formatVar($var);
    }

    /**
     * @deprecated
     */
    #[\ReturnTypeWillChange] public function formatDuration($seconds)
    {
        return $this->getDataFormatter()->formatDuration($seconds);
    }

    /**
     * @deprecated
     */
    #[\ReturnTypeWillChange] public function formatBytes($size, $precision = 2)
    {
        return $this->getDataFormatter()->formatBytes($size, $precision);
    }

    /**
     * @return string
     */
    #[\ReturnTypeWillChange] public function getXdebugLinkTemplate()
    {
        if (empty($this->xdebugLinkTemplate) && (!in_array(ini_get('xdebug.file_link_format'), ['', '0'], true) && ini_get('xdebug.file_link_format') !== false)) {
            $this->xdebugLinkTemplate = ini_get('xdebug.file_link_format');
        }

        return $this->xdebugLinkTemplate;
    }

    /**
     * @param string $xdebugLinkTemplate
     * @param bool $shouldUseAjax
     */
    #[\ReturnTypeWillChange] public function setXdebugLinkTemplate($xdebugLinkTemplate, $shouldUseAjax = false): void
    {
        if ($xdebugLinkTemplate === 'idea') {
            $this->xdebugLinkTemplate  = 'http://localhost:63342/api/file/?file=%f&line=%l';
            $this->xdebugShouldUseAjax = true;
        } else {
            $this->xdebugLinkTemplate  = $xdebugLinkTemplate;
            $this->xdebugShouldUseAjax = $shouldUseAjax;
        }
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange] public function getXdebugShouldUseAjax()
    {
        return $this->xdebugShouldUseAjax;
    }

    /**
     * returns an array of filename-replacements
     *
     * this is useful f.e. when using vagrant or remote servers,
     * where the path of the file is different between server and
     * development environment
     *
     * @return array key-value-pairs of replacements, key = path on server, value = replacement
     */
    #[\ReturnTypeWillChange] public function getXdebugReplacements()
    {
        return $this->xdebugReplacements;
    }

    /**
     * @param array $xdebugReplacements
     */
    #[\ReturnTypeWillChange] public function setXdebugReplacements($xdebugReplacements): void
    {
        $this->xdebugReplacements = $xdebugReplacements;
    }

    #[\ReturnTypeWillChange] public function setXdebugReplacement($serverPath, $replacement): void
    {
        $this->xdebugReplacements[$serverPath] = $replacement;
    }
}
