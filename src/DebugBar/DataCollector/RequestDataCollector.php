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
 * Collects info about the current request
 */
class RequestDataCollector extends DataCollector implements Renderable, AssetProvider
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

    #[\ReturnTypeWillChange] public function collect(): array
    {
        $vars = ['_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER'];
        $data = [];

        foreach ($vars as $var) {
            if (isset($GLOBALS[$var])) {
                $key = "$" . $var;
                if ($this->isHtmlVarDumperUsed()) {
                    $data[$key] = $this->getVarDumper()->renderVar($GLOBALS[$var]);
                } else {
                    $data[$key] = $this->getDataFormatter()->formatVar($GLOBALS[$var]);
                }
            }
        }

        return $data;
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'request';
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function getAssets() {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : [];
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return [
            "request" => [
                "icon" => "tags",
                "widget" => $widget,
                "map" => "request",
                "default" => "{}"
            ]
        ];
    }
}
