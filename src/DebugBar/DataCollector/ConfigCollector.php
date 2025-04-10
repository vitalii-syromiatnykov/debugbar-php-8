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

/**
 * Collects array data
 */
class ConfigCollector extends DataCollector implements Renderable, AssetProvider
{
    // The HTML var dumper requires debug bar users to support the new inline assets, which not all
    // may support yet - so return false by default for now.
    protected $useHtmlVarDumper = false;

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

    /**
     * @param string $name
     */
    #[\ReturnTypeWillChange]
    public function __construct(protected array $data = [], protected $name = 'config')
    {
    }

    /**
     * Sets the data
     */
    #[\ReturnTypeWillChange] public function setData(array $data): void
    {
        $this->data = $data;
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        $data = [];
        foreach ($this->data as $k => $v) {
            if ($this->isHtmlVarDumperUsed()) {
                $v = $this->getVarDumper()->renderVar($v);
            } elseif (!is_string($v)) {
                $v = $this->getDataFormatter()->formatVar($v);
            }

            $data[$k] = $v;
        }

        return $data;
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
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return [
            $name => [
                "icon" => "gear",
                "widget" => $widget,
                "map" => $name,
                "default" => "{}"
            ]
        ];
    }
}
