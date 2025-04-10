<?php

namespace DebugBar\Tests;

use DebugBar\DataCollector\MessagesCollector;
use DebugBar\JavascriptRenderer;

class JavascriptRendererTest extends DebugBarTestCase
{
    public $debugbar;
    /** @var JavascriptRenderer  */
    protected $r;

    #[\ReturnTypeWillChange]
    #[\Override] public function setUp(): void
    {
        parent::setUp();
        $this->r = new JavascriptRenderer($this->debugbar);
        $this->r->setBasePath('/bpath');
        $this->r->setBaseUrl('/burl');
    }

    #[\ReturnTypeWillChange] public function testOptions(): void
    {
        $this->r->setOptions([
            'base_path' => '/foo',
            'base_url' => '/foo',
            'include_vendors' => false,
            'javascript_class' => 'Foobar',
            'variable_name' => 'foovar',
            'initialization' => JavascriptRenderer::INITIALIZE_CONTROLS,
            'enable_jquery_noconflict' => true,
            'controls' => [
                'memory' => [
                    "icon" => "cogs",
                    "map" => "memory.peak_usage_str",
                    "default" => "'0B'"
                ]
            ],
            'disable_controls' => ['messages'],
            'ignore_collectors' => 'config',
            'ajax_handler_classname' => 'AjaxFoo',
            'ajax_handler_bind_to_jquery' => false,
            'ajax_handler_auto_show' => false,
            'open_handler_classname' => 'OpenFoo',
            'open_handler_url' => 'open.php'
        ]);

        $this->assertEquals('/foo', $this->r->getBasePath());
        $this->assertEquals('/foo', $this->r->getBaseUrl());
        $this->assertFalse($this->r->areVendorsIncluded());
        $this->assertEquals('Foobar', $this->r->getJavascriptClass());
        $this->assertEquals('foovar', $this->r->getVariableName());
        $this->assertEquals(JavascriptRenderer::INITIALIZE_CONTROLS, $this->r->getInitialization());
        $this->assertTrue($this->r->isJqueryNoConflictEnabled());
        $controls = $this->r->getControls();
        $this->assertCount(2, $controls);
        $this->assertArrayHasKey('memory', $controls);
        $this->assertArrayHasKey('messages', $controls);
        $this->assertNull($controls['messages']);
        $this->assertContains('config', $this->r->getIgnoredCollectors());
        $this->assertEquals('AjaxFoo', $this->r->getAjaxHandlerClass());
        $this->assertFalse($this->r->isAjaxHandlerBoundToJquery());
        $this->assertFalse($this->r->isAjaxHandlerAutoShow());
        $this->assertEquals('OpenFoo', $this->r->getOpenHandlerClass());
        $this->assertEquals('open.php', $this->r->getOpenHandlerUrl());
    }

    #[\ReturnTypeWillChange] public function testAddAssets(): void
    {
        // Use a loop to test deduplication of assets
        for ($i = 0; $i < 2; ++$i) {
            $this->r->addAssets('foo.css', 'foo.js', '/bar', '/foobar');
            $this->r->addInlineAssets(['Css' => 'CssTest'], ['Js' => 'JsTest'], ['Head' => 'HeaderTest']);
        }

        // Make sure all the right assets are returned by getAssets
        [$css, $js, $inline_css, $inline_js, $inline_head] = $this->r->getAssets();
        $this->assertContains('/bar/foo.css', $css);
        $this->assertContains('/bar/foo.js', $js);
        $this->assertEquals(['Css' => 'CssTest'], $inline_css);
        $this->assertEquals(['Js' => 'JsTest'], $inline_js);
        $this->assertEquals(['Head' => 'HeaderTest'], $inline_head);

        // Make sure asset files are deduplicated
        $this->assertCount(count(array_unique($css)), $css);
        $this->assertCount(count(array_unique($js)), $js);

        $html = $this->r->renderHead();
        $this->assertStringContainsString('<script type="text/javascript" src="/foobar/foo.js"></script>', $html);
    }

    #[\ReturnTypeWillChange] public function testGetAssets(): void
    {
        [$css, $js] = $this->r->getAssets();
        $this->assertContains('/bpath/debugbar.css', $css);
        $this->assertContains('/bpath/widgets.js', $js);
        $this->assertContains('/bpath/vendor/jquery/dist/jquery.min.js', $js);

        $this->r->setIncludeVendors(false);
        $js = $this->r->getAssets('js');
        $this->assertContains('/bpath/debugbar.js', $js);
        $this->assertNotContains('/bpath/vendor/jquery/dist/jquery.min.js', $js);
    }

    #[\ReturnTypeWillChange] public function testRenderHead(): void
    {
        $this->r->addInlineAssets(['Css' => 'CssTest'], ['Js' => 'JsTest'], ['Head' => 'HeaderTest']);

        $html = $this->r->renderHead();
        // Check for file links
        $this->assertStringContainsString('<link rel="stylesheet" type="text/css" href="/burl/debugbar.css">', $html);
        $this->assertStringContainsString('<script type="text/javascript" src="/burl/debugbar.js"></script>', $html);
        // Check for inline assets
        $this->assertStringContainsString('<style type="text/css">CssTest</style>', $html);
        $this->assertStringContainsString('<script type="text/javascript">JsTest</script>', $html);
        $this->assertStringContainsString('HeaderTest', $html);
        // Check jQuery noConflict
        $this->assertStringContainsString('jQuery.noConflict(true);', $html);

        // Check for absence of jQuery noConflict
        $this->r->setEnableJqueryNoConflict(false);
        $html = $this->r->renderHead();
        $this->assertStringNotContainsString('noConflict', $html);
    }

    #[\ReturnTypeWillChange] public function testRenderFullInitialization(): void
    {
        $this->debugbar->addCollector(new MessagesCollector());
        $this->r->addControl('time', ['icon' => 'time', 'map' => 'time', 'default' => '"0s"']);
        $expected = str_replace("\r\n", "\n", rtrim(file_get_contents(__DIR__ . '/full_init.html')));
        $this->assertStringStartsWith($expected, $this->r->render());
    }

    #[\ReturnTypeWillChange] public function testRenderConstructorOnly(): void
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setJavascriptClass('Foobar');
        $this->r->setVariableName('foovar');
        $this->r->setAjaxHandlerClass(false);
        $this->assertStringStartsWith("<script type=\"text/javascript\">\nvar foovar = new Foobar();\nfoovar.addDataSet(", $this->r->render());
    }

    #[\ReturnTypeWillChange] public function testRenderConstructorWithNonce(): void
    {
        $this->r->setInitialization(JavascriptRenderer::INITIALIZE_CONSTRUCTOR);
        $this->r->setCspNonce('mynonce');
        $this->assertStringStartsWith("<script type=\"text/javascript\" nonce=\"mynonce\">\nvar phpdebugbar = new PhpDebugBar.DebugBar();", $this->r->render());
    }

    #[\ReturnTypeWillChange] public function testJQueryNoConflictAutoDisabling(): void
    {
        $this->assertTrue($this->r->isJqueryNoConflictEnabled());
        $this->r->setIncludeVendors(false);
        $this->assertFalse($this->r->isJqueryNoConflictEnabled());
        $this->r->setEnableJqueryNoConflict(true);
        $this->r->setIncludeVendors('css');
        $this->assertFalse($this->r->isJqueryNoConflictEnabled());
        $this->r->setEnableJqueryNoConflict(true);
        $this->r->setIncludeVendors(['css', 'js']);
        $this->assertTrue($this->r->isJqueryNoConflictEnabled());
    }

    #[\ReturnTypeWillChange] public function testCanDisableSpecificVendors(): void
    {
        $this->assertStringContainsString('jquery.min.js', $this->r->renderHead());
        $this->r->disableVendor('jquery');
        $this->assertStringNotContainsString('jquery.min.js', $this->r->renderHead());
    }
}
