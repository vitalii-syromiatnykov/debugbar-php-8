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

use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;
use Assetic\Asset\AssetCollection;
use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\Renderable;

/**
 * Renders the debug bar using the client side javascript implementation
 *
 * Generates all the needed initialization code of controls
 */
class JavascriptRenderer
{
    const INITIALIZE_CONSTRUCTOR = 2;

    const INITIALIZE_CONTROLS = 4;

    const REPLACEABLE_TAG = "{--DEBUGBAR_OB_START_REPLACE_ME--}";

    const RELATIVE_PATH = 'path';

    const RELATIVE_URL = 'url';

    protected $baseUrl;

    protected $basePath;

    protected $cssVendors = [
        'fontawesome' => 'vendor/font-awesome/css/font-awesome.min.css',
        'highlightjs' => 'vendor/highlightjs/styles/github.css'
    ];

    protected $jsVendors = [
        'jquery' => 'vendor/jquery/dist/jquery.min.js',
        'highlightjs' => 'vendor/highlightjs/highlight.pack.js'
    ];

    protected $includeVendors = true;

    protected $cssFiles = ['debugbar.css', 'widgets.css', 'openhandler.css'];

    protected $jsFiles = ['debugbar.js', 'widgets.js', 'openhandler.js'];

    protected $additionalAssets = [];

    protected $javascriptClass = 'PhpDebugBar.DebugBar';

    protected $variableName = 'phpdebugbar';

    protected $enableJqueryNoConflict = true;

    protected $useRequireJs = false;

    protected int $initialization;

    protected $controls = [];

    protected $ignoredCollectors = [];

    protected $ajaxHandlerClass = 'PhpDebugBar.AjaxHandler';

    protected $ajaxHandlerBindToFetch = false;

    protected $ajaxHandlerBindToJquery = true;

    protected $ajaxHandlerBindToXHR = false;

    protected $ajaxHandlerAutoShow = true;

    protected $openHandlerClass = 'PhpDebugBar.OpenHandler';

    protected $openHandlerUrl;

    protected $cspNonce;

    /**
     * @param string $baseUrl
     * @param string $basePath
     */
    #[\ReturnTypeWillChange] public function __construct(protected DebugBar $debugBar, $baseUrl = null, $basePath = null)
    {
        if ($baseUrl === null) {
            $baseUrl = '/vendor/maximebf/debugbar/src/DebugBar/Resources';
        }

        $this->baseUrl = $baseUrl;

        if ($basePath === null) {
            $basePath = __DIR__ . DIRECTORY_SEPARATOR . 'Resources';
        }

        $this->basePath = $basePath;

        // bitwise operations cannot be done in class definition :(
        $this->initialization = self::INITIALIZE_CONSTRUCTOR | self::INITIALIZE_CONTROLS;
    }

    /**
     * Sets options from an array
     *
     * Options:
     *  - base_path
     *  - base_url
     *  - include_vendors
     *  - javascript_class
     *  - variable_name
     *  - initialization
     *  - enable_jquery_noconflict
     *  - controls
     *  - disable_controls
     *  - ignore_collectors
     *  - ajax_handler_classname
     *  - ajax_handler_bind_to_jquery
     *  - ajax_handler_auto_show
     *  - open_handler_classname
     *  - open_handler_url
     *
     * @param array $options [description]
     */
    #[\ReturnTypeWillChange] public function setOptions(array $options): void
    {
        if (array_key_exists('base_path', $options)) {
            $this->setBasePath($options['base_path']);
        }

        if (array_key_exists('base_url', $options)) {
            $this->setBaseUrl($options['base_url']);
        }

        if (array_key_exists('include_vendors', $options)) {
            $this->setIncludeVendors($options['include_vendors']);
        }

        if (array_key_exists('javascript_class', $options)) {
            $this->setJavascriptClass($options['javascript_class']);
        }

        if (array_key_exists('variable_name', $options)) {
            $this->setVariableName($options['variable_name']);
        }

        if (array_key_exists('initialization', $options)) {
            $this->setInitialization($options['initialization']);
        }

        if (array_key_exists('enable_jquery_noconflict', $options)) {
            $this->setEnableJqueryNoConflict($options['enable_jquery_noconflict']);
        }

        if (array_key_exists('use_requirejs', $options)) {
            $this->setUseRequireJs($options['use_requirejs']);
        }

        if (array_key_exists('controls', $options)) {
            foreach ($options['controls'] as $name => $control) {
                $this->addControl($name, $control);
            }
        }

        if (array_key_exists('disable_controls', $options)) {
            foreach ((array) $options['disable_controls'] as $name) {
                $this->disableControl($name);
            }
        }

        if (array_key_exists('ignore_collectors', $options)) {
            foreach ((array) $options['ignore_collectors'] as $name) {
                $this->ignoreCollector($name);
            }
        }

        if (array_key_exists('ajax_handler_classname', $options)) {
            $this->setAjaxHandlerClass($options['ajax_handler_classname']);
        }

        if (array_key_exists('ajax_handler_bind_to_jquery', $options)) {
            $this->setBindAjaxHandlerToJquery($options['ajax_handler_bind_to_jquery']);
        }

        if (array_key_exists('ajax_handler_auto_show', $options)) {
            $this->setAjaxHandlerAutoShow($options['ajax_handler_auto_show']);
        }

        if (array_key_exists('open_handler_classname', $options)) {
            $this->setOpenHandlerClass($options['open_handler_classname']);
        }

        if (array_key_exists('open_handler_url', $options)) {
            $this->setOpenHandlerUrl($options['open_handler_url']);
        }

        if (array_key_exists('csp_nonce', $options)) {
            $this->setCspNonce($options['csp_nonce']);
        }
    }

    /**
     * Sets the path which assets are relative to
     *
     * @param string $path
     */
    #[\ReturnTypeWillChange] public function setBasePath($path): static
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Returns the path which assets are relative to
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Sets the base URL from which assets will be served
     *
     * @param string $url
     */
    #[\ReturnTypeWillChange] public function setBaseUrl($url): static
    {
        $this->baseUrl = $url;
        return $this;
    }

    /**
     * Returns the base URL from which assets will be served
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Whether to include vendor assets
     *
     * You can only include js or css vendors using
     * setIncludeVendors('css') or setIncludeVendors('js')
     *
     * @param boolean $enabled
     */
    #[\ReturnTypeWillChange] public function setIncludeVendors($enabled = true): static
    {
        if (is_string($enabled)) {
            $enabled = [$enabled];
        }

        $this->includeVendors = $enabled;

        if (!$enabled || (is_array($enabled) && !in_array('js', $enabled))) {
            // no need to call jQuery.noConflict() if we do not include our own version
            $this->enableJqueryNoConflict = false;
        }

        return $this;
    }

    /**
     * Checks if vendors assets are included
     */
    #[\ReturnTypeWillChange] public function areVendorsIncluded(): bool
    {
        return $this->includeVendors !== false;
    }

    /**
     * Disable a specific vendor's assets.
     *
     * @param  string $name "jquery", "fontawesome", "highlightjs"
     */
    #[\ReturnTypeWillChange] public function disableVendor($name): void
    {
        if (array_key_exists($name, $this->cssVendors)) {
            unset($this->cssVendors[$name]);
        }

        if (array_key_exists($name, $this->jsVendors)) {
            unset($this->jsVendors[$name]);
        }
    }

    /**
     * Sets the javascript class name
     *
     * @param string $className
     */
    #[\ReturnTypeWillChange] public function setJavascriptClass($className): static
    {
        $this->javascriptClass = $className;
        return $this;
    }

    /**
     * Returns the javascript class name
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getJavascriptClass()
    {
        return $this->javascriptClass;
    }

    /**
     * Sets the variable name of the class instance
     *
     * @param string $name
     */
    #[\ReturnTypeWillChange] public function setVariableName($name): static
    {
        $this->variableName = $name;
        return $this;
    }

    /**
     * Returns the variable name of the class instance
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getVariableName()
    {
        return $this->variableName;
    }

    /**
     * Sets what should be initialized
     *
     *  - INITIALIZE_CONSTRUCTOR: only initializes the instance
     *  - INITIALIZE_CONTROLS: initializes the controls and data mapping
     *  - INITIALIZE_CONSTRUCTOR | INITIALIZE_CONTROLS: initialize everything (default)
     *
     * @param integer $init
     */
    #[\ReturnTypeWillChange] public function setInitialization($init): static
    {
        $this->initialization = $init;
        return $this;
    }

    /**
     * Returns what should be initialized
     *
     * @return integer
     */
    #[\ReturnTypeWillChange] public function getInitialization()
    {
        return $this->initialization;
    }

    /**
     * Sets whether to call jQuery.noConflict()
     *
     * @param boolean $enabled
     */
    #[\ReturnTypeWillChange] public function setEnableJqueryNoConflict($enabled = true): static
    {
        $this->enableJqueryNoConflict = $enabled;
        return $this;
    }

    /**
     * Checks if jQuery.noConflict() will be called
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function isJqueryNoConflictEnabled()
    {
        return $this->enableJqueryNoConflict;
    }

    /**
     * Sets whether to use RequireJS or not
     *
     * @param boolean $enabled
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setUseRequireJs($enabled = true): static
    {
        $this->useRequireJs = $enabled;
        return $this;
    }

    /**
     * Checks if RequireJS is used
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function isRequireJsUsed()
    {
        return $this->useRequireJs;
    }

    /**
     * Adds a control to initialize
     *
     * Possible options:
     *  - icon: icon name
     *  - tooltip: string
     *  - widget: widget class name
     *  - title: tab title
     *  - map: a property name from the data to map the control to
     *  - default: a js string, default value of the data map
     *
     * "icon" or "widget" are at least needed
     *
     * @param string $name
     */
    #[\ReturnTypeWillChange] public function addControl($name, array $options): static
    {
        if (array_intersect(array_keys($options), ['icon', 'widget', 'tab', 'indicator']) === []) {
            throw new DebugBarException(sprintf("Not enough options for control '%s'", $name));
        }

        $this->controls[$name] = $options;
        return $this;
    }

    /**
     * Disables a control
     *
     * @param string $name
     */
    #[\ReturnTypeWillChange] public function disableControl($name): static
    {
        $this->controls[$name] = null;
        return $this;
    }

    /**
     * Returns the list of controls
     *
     * This does not include controls provided by collectors
     *
     * @return array
     */
    #[\ReturnTypeWillChange] public function getControls()
    {
        return $this->controls;
    }

    /**
     * Ignores widgets provided by a collector
     *
     * @param string $name
     */
    #[\ReturnTypeWillChange] public function ignoreCollector($name): static
    {
        $this->ignoredCollectors[] = $name;
        return $this;
    }

    /**
     * Returns the list of ignored collectors
     *
     * @return array
     */
    #[\ReturnTypeWillChange] public function getIgnoredCollectors()
    {
        return $this->ignoredCollectors;
    }

    /**
     * Sets the class name of the ajax handler
     *
     * Set to false to disable
     *
     * @param string $className
     */
    #[\ReturnTypeWillChange] public function setAjaxHandlerClass($className): static
    {
        $this->ajaxHandlerClass = $className;
        return $this;
    }

    /**
     * Returns the class name of the ajax handler
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getAjaxHandlerClass()
    {
        return $this->ajaxHandlerClass;
    }

    /**
     * Sets whether to call bindToFetch() on the ajax handler
     *
     * @param boolean $bind
     */
    #[\ReturnTypeWillChange] public function setBindAjaxHandlerToFetch($bind = true): static
    {
        $this->ajaxHandlerBindToFetch = $bind;
        return $this;
    }

    /**
     * Checks whether bindToFetch() will be called on the ajax handler
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function isAjaxHandlerBoundToFetch()
    {
        return $this->ajaxHandlerBindToFetch;
    }

    /**
     * Sets whether to call bindToJquery() on the ajax handler
     *
     * @param boolean $bind
     */
    #[\ReturnTypeWillChange] public function setBindAjaxHandlerToJquery($bind = true): static
    {
        $this->ajaxHandlerBindToJquery = $bind;
        return $this;
    }

    /**
     * Checks whether bindToJquery() will be called on the ajax handler
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function isAjaxHandlerBoundToJquery()
    {
        return $this->ajaxHandlerBindToJquery;
    }

    /**
     * Sets whether to call bindToXHR() on the ajax handler
     *
     * @param boolean $bind
     */
    #[\ReturnTypeWillChange] public function setBindAjaxHandlerToXHR($bind = true): static
    {
        $this->ajaxHandlerBindToXHR = $bind;
        return $this;
    }

    /**
     * Checks whether bindToXHR() will be called on the ajax handler
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function isAjaxHandlerBoundToXHR()
    {
        return $this->ajaxHandlerBindToXHR;
    }

    /**
     * Sets whether new ajax debug data will be immediately shown.  Setting to false could be useful
     * if there are a lot of tracking events cluttering things.
     *
     * @param boolean $autoShow
     */
    #[\ReturnTypeWillChange] public function setAjaxHandlerAutoShow($autoShow = true): static
    {
        $this->ajaxHandlerAutoShow = $autoShow;
        return $this;
    }

    /**
     * Checks whether the ajax handler will immediately show new ajax requests.
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange] public function isAjaxHandlerAutoShow()
    {
        return $this->ajaxHandlerAutoShow;
    }

    /**
     * Sets the class name of the js open handler
     *
     * @param string $className
     */
    #[\ReturnTypeWillChange] public function setOpenHandlerClass($className): static
    {
        $this->openHandlerClass = $className;
        return $this;
    }

    /**
     * Returns the class name of the js open handler
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getOpenHandlerClass()
    {
        return $this->openHandlerClass;
    }

    /**
     * Sets the url of the open handler
     *
     * @param string $url
     */
    #[\ReturnTypeWillChange] public function setOpenHandlerUrl($url): static
    {
        $this->openHandlerUrl = $url;
        return $this;
    }

    /**
     * Returns the url for the open handler
     *
     * @return string
     */
    #[\ReturnTypeWillChange] public function getOpenHandlerUrl()
    {
        return $this->openHandlerUrl;
    }

    /**
     * Sets the CSP Nonce (or remove it by setting to null)
     *
     * @param string|null $nonce
     * @return $this
     */
    #[\ReturnTypeWillChange] public function setCspNonce($nonce = null): static
    {
        $this->cspNonce = $nonce;
        return $this;
    }

    /**
     * Get the CSP Nonce
     *
     * @return string|null
     */
    #[\ReturnTypeWillChange] public function getCspNonce()
    {
        return $this->cspNonce;
    }

    /**
     * Add assets stored in files to render in the head
     *
     * @param array $cssFiles An array of filenames
     * @param array $jsFiles  An array of filenames
     * @param string $basePath Base path of those files
     * @param string $baseUrl  Base url of those files
     * @return $this
     */
    #[\ReturnTypeWillChange] public function addAssets($cssFiles, $jsFiles, $basePath = null, $baseUrl = null): static
    {
        $this->additionalAssets[] = [
            'base_path' => $basePath,
            'base_url' => $baseUrl,
            'css' => (array) $cssFiles,
            'js' => (array) $jsFiles
        ];
        return $this;
    }

    /**
     * Add inline assets to render inline in the head.  Ideally, you should store static assets in
     * files that you add with the addAssets function.  However, adding inline assets is useful when
     * integrating with 3rd-party libraries that require static assets that are only available in an
     * inline format.
     *
     * The inline content arrays require special string array keys:  they are used to deduplicate
     * content.  This is particularly useful if multiple instances of the same asset end up being
     * added.  Inline assets from all collectors are merged together into the same array, so these
     * content IDs effectively deduplicate the inline assets.
     *
     * @param array $inlineCss  An array map of content ID to inline CSS content (not including <style> tag)
     * @param array $inlineJs   An array map of content ID to inline JS content (not including <script> tag)
     * @param array $inlineHead An array map of content ID to arbitrary inline HTML content (typically
     *                          <style>/<script> tags); it must be embedded within the <head> element
     * @return $this
     */
    #[\ReturnTypeWillChange] public function addInlineAssets($inlineCss, $inlineJs, $inlineHead): static
    {
        $this->additionalAssets[] = [
            'inline_css' => (array) $inlineCss,
            'inline_js' => (array) $inlineJs,
            'inline_head' => (array) $inlineHead
        ];
        return $this;
    }

    /**
     * Returns the list of asset files
     *
     * @param string $type 'css', 'js', 'inline_css', 'inline_js', 'inline_head', or null for all
     * @param string $relativeTo The type of path to which filenames must be relative (path, url or null)
     * @return array
     */
    #[\ReturnTypeWillChange] public function getAssets($type = null, $relativeTo = self::RELATIVE_PATH)
    {
        $cssFiles = $this->cssFiles;
        $jsFiles = $this->jsFiles;
        $inlineCss = [];
        $inlineJs = [];
        $inlineHead = [];

        if ($this->includeVendors !== false) {
            if ($this->includeVendors === true || in_array('css', $this->includeVendors)) {
                $cssFiles = array_merge($this->cssVendors, $cssFiles);
            }

            if ($this->includeVendors === true || in_array('js', $this->includeVendors)) {
                $jsFiles = array_merge($this->jsVendors, $jsFiles);
            }
        }

        if ($relativeTo) {
            $root = $this->getRelativeRoot($relativeTo, $this->basePath, $this->baseUrl);
            $cssFiles = $this->makeUriRelativeTo($cssFiles, $root);
            $jsFiles = $this->makeUriRelativeTo($jsFiles, $root);
        }

        $additionalAssets = $this->additionalAssets;
        // finds assets provided by collectors
        foreach ($this->debugBar->getCollectors() as $collector) {
            if (($collector instanceof AssetProvider) && !in_array($collector->getName(), $this->ignoredCollectors)) {
                $additionalAssets[] = $collector->getAssets();
            }
        }

        foreach ($additionalAssets as $assets) {
            $basePath = $assets['base_path'] ?? '';
            $baseUrl = $assets['base_url'] ?? '';
            $root = $this->getRelativeRoot($relativeTo,
                $this->makeUriRelativeTo($basePath, $this->basePath),
                $this->makeUriRelativeTo($baseUrl, $this->baseUrl));
            if (isset($assets['css'])) {
                $cssFiles = array_merge($cssFiles, $this->makeUriRelativeTo((array) $assets['css'], $root));
            }

            if (isset($assets['js'])) {
                $jsFiles = array_merge($jsFiles, $this->makeUriRelativeTo((array) $assets['js'], $root));
            }

            if (isset($assets['inline_css'])) {
                $inlineCss = array_merge($inlineCss, (array) $assets['inline_css']);
            }

            if (isset($assets['inline_js'])) {
                $inlineJs = array_merge($inlineJs, (array) $assets['inline_js']);
            }

            if (isset($assets['inline_head'])) {
                $inlineHead = array_merge($inlineHead, (array) $assets['inline_head']);
            }
        }

        // Deduplicate files
        $cssFiles = array_unique($cssFiles);
        $jsFiles = array_unique($jsFiles);

        return $this->filterAssetArray([$cssFiles, $jsFiles, $inlineCss, $inlineJs, $inlineHead], $type ?? '');
    }

    /**
     * Returns the correct base according to the type
     *
     * @param string $relativeTo
     * @param string $basePath
     * @param string $baseUrl
     * @return string
     */
    protected function getRelativeRoot($relativeTo, $basePath, $baseUrl)
    {
        if ($relativeTo === self::RELATIVE_PATH) {
            return $basePath;
        }

        if ($relativeTo === self::RELATIVE_URL) {
            return $baseUrl;
        }

        return null;
    }

    /**
     * Makes a URI relative to another
     *
     * @param string|array $uri
     * @param string $root
     * @return string
     */
    protected function makeUriRelativeTo($uri, $root)
    {
        if (!$root) {
            return $uri;
        }

        if (is_array($uri)) {
            $uris = [];
            foreach ($uri as $u) {
                $uris[] = $this->makeUriRelativeTo($u, $root);
            }

            return $uris;
        }

        $uri ??= '';

        if (str_starts_with($uri, '/') || preg_match('/^([a-zA-Z]+:\/\/|[a-zA-Z]:\/|[a-zA-Z]:\\\)/', $uri)) {
            return $uri;
        }

        return rtrim($root, '/') . ('/' . $uri);
    }

    /**
     * Filters a tuple of (css, js, inline_css, inline_js, inline_head) assets according to $type
     *
     * @param string $type 'css', 'js', 'inline_css', 'inline_js', 'inline_head', or null for all
     * @return array
     */
    protected function filterAssetArray(array $array, $type = '')
    {
        $types = ['css', 'js', 'inline_css', 'inline_js', 'inline_head'];
        $typeIndex = array_search(strtolower($type ?? ''), $types, true);
        return $typeIndex !== false ? $array[$typeIndex] : $array;
    }

    /**
     * Returns an array where all items are Assetic AssetCollection:
     *  - The first one contains the CSS files
     *  - The second one contains the JS files
     *  - The third one contains arbitrary inline HTML (typically composed of <script>/<style>
     *    elements); it must be embedded within the <head> element
     *
     * @param string $type Optionally return only 'css', 'js', or 'inline_head' collection
     * @return array|\Assetic\Asset\AssetCollection
     */
    #[\ReturnTypeWillChange] public function getAsseticCollection($type = null)
    {
        $types = ['css', 'js', 'inline_head'];
        $typeIndex = array_search(strtolower((string) $type), $types, true);

        [$cssFiles, $jsFiles, $inlineCss, $inlineJs, $inlineHead] = $this->getAssets();
        $collections = [
            $this->createAsseticCollection($cssFiles, $inlineCss),
            $this->createAsseticCollection($jsFiles, $inlineJs),
            $this->createAsseticCollection(null, $inlineHead)
        ];
        return $typeIndex !== false ? $collections[$typeIndex] : $collections;
    }

    /**
     * Create an Assetic AssetCollection with the given content.
     * Filenames will be converted to absolute path using
     * the base path.
     *
     * @param array|null $files Array of asset filenames.
     * @param array|null $content Array of inline asset content.
     * @return \Assetic\Asset\AssetCollection
     */
    protected function createAsseticCollection($files = null, $content = null)
    {
        $assets = [];
        if ($files) {
            foreach ($files as $file) {
                $assets[] = new FileAsset($file);
            }
        }

        if ($content) {
            foreach ($content as $item) {
                $assets[] = new StringAsset($item);
            }
        }

        return new AssetCollection($assets);
    }

    /**
     * Write all CSS assets to standard output or in a file
     *
     * @param string $targetFilename
     */
    #[\ReturnTypeWillChange] public function dumpCssAssets($targetFilename = null): void
    {
        $this->dumpAssets($this->getAssets('css'), $this->getAssets('inline_css'), $targetFilename);
    }

    /**
     * Write all JS assets to standard output or in a file
     *
     * @param string $targetFilename
     */
    #[\ReturnTypeWillChange] public function dumpJsAssets($targetFilename = null): void
    {
        $this->dumpAssets($this->getAssets('js'), $this->getAssets('inline_js'), $targetFilename, $this->useRequireJs);
    }

    /**
     * Write all inline HTML header assets to standard output or in a file (only returns assets not
     * already returned by dumpCssAssets or dumpJsAssets)
     *
     * @param string $targetFilename
     */
    #[\ReturnTypeWillChange] public function dumpHeadAssets($targetFilename = null): void
    {
        $this->dumpAssets(null, $this->getAssets('inline_head'), $targetFilename);
    }

    /**
     * Write assets to standard output or in a file
     *
     * @param array|null $files Filenames containing assets
     * @param array|null $content Inline content to dump
     * @param string $targetFilename
     * @param bool $useRequireJs
     */
    protected function dumpAssets($files = null, $content = null, $targetFilename = null, $useRequireJs = false)
    {
        $dumpedContent = '';
        if ($files) {
            foreach ($files as $file) {
                $dumpedContent .= file_get_contents($file) . "\n";
            }
        }

        if ($content) {
            foreach ($content as $item) {
                $dumpedContent .= $item . "\n";
            }
        }

        if ($useRequireJs) {
            $dumpedContent = "define('debugbar', ['jquery'], function($){\r\n" . $dumpedContent . "\r\n return PhpDebugBar; \r\n});";
        }

        if ($targetFilename !== null) {
            file_put_contents($targetFilename, $dumpedContent);
        } else {
            echo $dumpedContent;
        }
    }

    /**
     * Renders the html to include needed assets
     *
     * Only useful if Assetic is not used
     */
    #[\ReturnTypeWillChange] public function renderHead(): string
    {
        [$cssFiles, $jsFiles, $inlineCss, $inlineJs, $inlineHead] = $this->getAssets(null, self::RELATIVE_URL);
        $html = '';

        $nonce = $this->getNonceAttribute();

        foreach ($cssFiles as $file) {
            $html .= sprintf('<link rel="stylesheet" type="text/css" href="%s">' . "\n", $file);
        }

        foreach ($inlineCss as $content) {
            $html .= sprintf('<style type="text/css">%s</style>' . "\n", $content);
        }

        foreach ($jsFiles as $file) {
            $html .= sprintf('<script type="text/javascript" src="%s"></script>' . "\n", $file);
        }

        foreach ($inlineJs as $content) {
            $html .= sprintf('<script type="text/javascript"%s>%s</script>' . "\n", $nonce, $content);
        }

        foreach ($inlineHead as $content) {
            $html .= $content . "\n";
        }

        if ($this->enableJqueryNoConflict && !$this->useRequireJs) {
            $html .= '<script type="text/javascript"' . $nonce . '>jQuery.noConflict(true);</script>' . "\n";
        }

        return $html;
    }

    /**
     * Register shutdown to display the debug bar
     *
     * @param boolean $here Set position of HTML. True if is to current position or false for end file
     * @param boolean $initialize Whether to render the de bug bar initialization code
     * @param bool $renderStackedData
     * @param bool $head
     * @return string Return "{--DEBUGBAR_OB_START_REPLACE_ME--}" or return an empty string if $here == false
     */
    #[\ReturnTypeWillChange] public function renderOnShutdown($here = true, $initialize = true, $renderStackedData = true, $head = false): string
    {
        register_shutdown_function([$this, "replaceTagInBuffer"], $here, $initialize, $renderStackedData, $head);

        if (ob_get_level() === 0) {
            ob_start();
        }

        return ($here) ? self::REPLACEABLE_TAG : "";
    }

    /**
     * Same as renderOnShutdown() with $head = true
     *
     * @param boolean $here
     * @param boolean $initialize
     * @param boolean $renderStackedData
     * @return string
     */
    #[\ReturnTypeWillChange] public function renderOnShutdownWithHead($here = true, $initialize = true, $renderStackedData = true)
    {
        return $this->renderOnShutdown($here, $initialize, $renderStackedData, true);
    }

    /**
     * Is callback function for register_shutdown_function(...)
     *
     * @param boolean $here Set position of HTML. True if is to current position or false for end file
     * @param boolean $initialize Whether to render the de bug bar initialization code
     * @param bool $renderStackedData
     * @param bool $head
     */
    #[\ReturnTypeWillChange] public function replaceTagInBuffer($here = true, $initialize = true, $renderStackedData = true, $head = false): void
    {
        $render = ($head ? $this->renderHead() : "")
                . $this->render($initialize, $renderStackedData);

        $current = ($here && ob_get_level() > 0) ? ob_get_clean() : self::REPLACEABLE_TAG;

        echo str_replace(self::REPLACEABLE_TAG, $render, $current, $count);

        if ($count === 0) {
            echo $render;
        }
    }

    /**
     * Returns the code needed to display the debug bar
     *
     * AJAX request should not render the initialization code.
     *
     * @param boolean $initialize Whether or not to render the debug bar initialization code
     * @param boolean $renderStackedData Whether or not to render the stacked data
     */
    #[\ReturnTypeWillChange] public function render($initialize = true, $renderStackedData = true): string
    {
        $js = '';

        if ($initialize) {
            $js = $this->getJsInitializationCode();
        }

        if ($renderStackedData && $this->debugBar->hasStackedData()) {
            foreach ($this->debugBar->getStackedData() as $id => $data) {
                $js .= $this->getAddDatasetCode($id, $data, '(stacked)');
            }
        }

        $suffix = $initialize ? null : '(ajax)';
        $js .= $this->getAddDatasetCode($this->debugBar->getCurrentRequestId(), $this->debugBar->getData(), $suffix);

        $nonce = $this->getNonceAttribute();

        if ($this->useRequireJs){
            return "<script type=\"text/javascript\"{$nonce}>\nrequire(['debugbar'], function(PhpDebugBar){ {$js} });\n</script>\n";
        } else {
            return "<script type=\"text/javascript\"{$nonce}>\n{$js}\n</script>\n";
        }

    }

    /**
     * Returns the js code needed to initialize the debug bar
     */
    protected function getJsInitializationCode(): string
    {
        $js = '';

        if (($this->initialization & self::INITIALIZE_CONSTRUCTOR) === self::INITIALIZE_CONSTRUCTOR) {
            $js .= sprintf("var %s = new %s();\n", $this->variableName, $this->javascriptClass);
        }

        if (($this->initialization & self::INITIALIZE_CONTROLS) === self::INITIALIZE_CONTROLS) {
            $js .= $this->getJsControlsDefinitionCode($this->variableName);
        }

        if ($this->ajaxHandlerClass) {
            $js .= sprintf("%s.ajaxHandler = new %s(%s, undefined, %s);\n",
                $this->variableName,
                $this->ajaxHandlerClass,
                $this->variableName,
                $this->ajaxHandlerAutoShow ? 'true' : 'false'
            );
            if ($this->ajaxHandlerBindToFetch) {
                $js .= sprintf("%s.ajaxHandler.bindToFetch();\n", $this->variableName);
            }

            if ($this->ajaxHandlerBindToXHR) {
                $js .= sprintf("%s.ajaxHandler.bindToXHR();\n", $this->variableName);
            } elseif ($this->ajaxHandlerBindToJquery) {
                $js .= sprintf("if (jQuery) %s.ajaxHandler.bindToJquery(jQuery);\n", $this->variableName);
            }
        }

        if ($this->openHandlerUrl !== null) {
            $js .= sprintf("%s.setOpenHandler(new %s(%s));\n", $this->variableName,
                $this->openHandlerClass,
                json_encode(["url" => $this->openHandlerUrl]));
        }

        return $js;
    }

    /**
     * Returns the js code needed to initialized the controls and data mapping of the debug bar
     *
     * Controls can be defined by collectors themselves or using {@see addControl()}
     *
     * @param string $varname Debug bar's variable name
     */
    protected function getJsControlsDefinitionCode($varname): string
    {
        $js = '';
        $dataMap = [];
        $excludedOptions = ['indicator', 'tab', 'map', 'default', 'widget', 'position'];

        // finds controls provided by collectors
        $widgets = [];
        foreach ($this->debugBar->getCollectors() as $collector) {
            if ($collector instanceof Renderable && !in_array($collector->getName(), $this->ignoredCollectors) && $w = $collector->getWidgets()) {
                $widgets = array_merge($widgets, $w);
            }
        }

        $controls = array_merge($widgets, $this->controls);

        foreach (array_filter($controls) as $name => $options) {
            $opts = array_diff_key($options, array_flip($excludedOptions));

            if (isset($options['tab']) || isset($options['widget'])) {
                if (!isset($opts['title'])) {
                    $opts['title'] = ucfirst(str_replace('_', ' ', $name));
                }

                $js .= sprintf("%s.addTab(\"%s\", new %s({%s%s}));\n",
                    $varname,
                    $name,
                    $options['tab'] ?? 'PhpDebugBar.DebugBar.Tab',
                    substr(json_encode($opts, JSON_FORCE_OBJECT), 1, -1),
                    isset($options['widget']) ? sprintf('%s"widget": new %s()', count($opts) ? ', ' : '', $options['widget']) : ''
                );
            } elseif (isset($options['indicator']) || isset($options['icon'])) {
                $js .= sprintf("%s.addIndicator(\"%s\", new %s(%s), \"%s\");\n",
                    $varname,
                    $name,
                    $options['indicator'] ?? 'PhpDebugBar.DebugBar.Indicator',
                    json_encode($opts, JSON_FORCE_OBJECT),
                    $options['position'] ?? 'right'
                );
            }

            if (isset($options['map']) && isset($options['default'])) {
                $dataMap[$name] = [$options['map'], $options['default']];
            }
        }

        // creates the data mapping object
        $mapJson = [];
        foreach ($dataMap as $name => $values) {
            $mapJson[] = sprintf('"%s": ["%s", %s]', $name, $values[0], $values[1]);
        }

        $js .= sprintf("%s.setDataMap({\n%s\n});\n", $varname, implode(",\n", $mapJson));

        // activate state restoration
        $js .= sprintf("%s.restoreState();\n", $varname);

        return $js;
    }

    /**
     * Returns the js code needed to add a dataset
     *
     * @param string $requestId
     * @param array $data
     * @param mixed $suffix
     */
    protected function getAddDatasetCode($requestId, $data, $suffix = null): string
    {
        return sprintf("%s.addDataSet(%s, \"%s\"%s);\n",
            $this->variableName,
            json_encode($data),
            $requestId,
            $suffix ? ", " . json_encode($suffix) : ''
        );
    }

    /**
     * If a nonce it set, create the correct attribute
     */
    protected function getNonceAttribute(): string
    {
        if ($nonce = $this->getCspNonce()) {
            return ' nonce="' . $nonce .'"';
        }

        return '';
    }
}
