<?php

namespace DebugBar\Tests\DataFormatter;

use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use DebugBar\DataFormatter\DebugBarVarDumper;
use DebugBar\Tests\DebugBarTestCase;

class DebugBarVarDumperTest extends DebugBarTestCase
{
    const STYLE_STRING = 'SpecialStyleString';

    private array $testStyles = [
        'default' => self::STYLE_STRING,
    ];

    #[\ReturnTypeWillChange] public function testBasicFunctionality(): void
    {
        // Test that we can render a simple variable without dump headers
        $d = new DebugBarVarDumper();
        $d->mergeDumperOptions(['styles' => $this->testStyles]);

        $out = $d->renderVar('magic');

        $this->assertStringContainsString('magic', $out);
        $this->assertStringNotContainsString(self::STYLE_STRING, $out); // make sure there's no dump header

        // Test that we can capture a variable without rendering into a Data-type variable
        $data = $d->captureVar('hello');
        $this->assertStringContainsString('hello', $data);
        $deserialized = unserialize($data);
        $this->assertInstanceOf(Data::class, $deserialized);

        // Test that we can render the captured variable at a later time
        $out = $d->renderCapturedVar($data);
        $this->assertStringContainsString('hello', $out);
        $this->assertStringNotContainsString(self::STYLE_STRING, $out); // make sure there's no dump header
    }

    #[\ReturnTypeWillChange] public function testSeeking(): void
    {
        $testData = [
            'one',
            ['two'],
            'three',
        ];
        $d = new DebugBarVarDumper();
        $data = $d->captureVar($testData);

        // seek depth of 1
        $out = $d->renderCapturedVar($data, [1]);
        $this->assertStringNotContainsString('one', $out);
        $this->assertStringContainsString('array', $out);
        $this->assertStringContainsString('two', $out);
        $this->assertStringNotContainsString('three', $out);

        // seek depth of 2
        $out = $d->renderCapturedVar($data, [1, 0]);
        $this->assertStringNotContainsString('one', $out);
        $this->assertStringNotContainsString('array', $out);
        $this->assertStringContainsString('two', $out);
        $this->assertStringNotContainsString('three', $out);
    }

    #[\ReturnTypeWillChange] public function testAssetProvider(): void
    {
        $d = new DebugBarVarDumper();
        $d->mergeDumperOptions(['styles' => $this->testStyles]);

        $assets = $d->getAssets();
        $this->assertArrayHasKey('inline_head', $assets);
        $this->assertCount(1, $assets);

        $inlineHead = $assets['inline_head'];
        $this->assertArrayHasKey('html_var_dumper', $inlineHead);
        $this->assertCount(1, $inlineHead);

        $assetText = $inlineHead['html_var_dumper'];
        $this->assertStringContainsString(self::STYLE_STRING, $assetText);
    }

    #[\ReturnTypeWillChange] public function testBasicOptionOperations(): void
    {
        // Test basic get/merge/reset functionality for cloner
        $d = new DebugBarVarDumper();
        $options = $d->getClonerOptions();
        $this->assertEmpty($options);

        $d->mergeClonerOptions([
            'max_items' => 5,
        ]);
        $d->mergeClonerOptions([
            'max_string' => 4,
        ]);
        $d->mergeClonerOptions([
            'max_items' => 3,
        ]);
        $options = $d->getClonerOptions();
        $this->assertEquals([
            'max_items' => 3,
            'max_string' => 4,
        ], $options);

        $d->resetClonerOptions([
            'min_depth' => 2,
        ]);
        $options = $d->getClonerOptions();
        $this->assertEquals([
            'min_depth' => 2,
        ], $options);

        // Test basic get/merge/reset functionality for dumper
        $options = $d->getDumperOptions();
        $this->assertArrayHasKey('styles', $options);
        $this->assertArrayHasKey('const', $options['styles']);
        $this->assertArrayHasKey('expanded_depth', $options);
        $this->assertEquals(0, $options['expanded_depth']);
        $this->assertCount(2, $options);

        $d->mergeDumperOptions([
            'styles' => $this->testStyles,
        ]);
        $d->mergeDumperOptions([
            'max_string' => 7,
        ]);
        $options = $d->getDumperOptions();
        $this->assertEquals([
            'max_string' => 7,
            'styles' => $this->testStyles,
            'expanded_depth' => 0,
        ], $options);

        $d->resetDumperOptions([
            'styles' => $this->testStyles,
        ]);
        $options = $d->getDumperOptions();
        $this->assertEquals([
            'styles' => $this->testStyles,
            'expanded_depth' => 0,
        ], $options);
    }

    #[\ReturnTypeWillChange] public function testClonerOptions(): void
    {
        // Test the actual operation of the cloner options
        $d = new DebugBarVarDumper();

        // Test that the 'casters' option can remove default casters
        $testData = function(): void {};
        $d->resetClonerOptions();
        $this->assertStringContainsString('DebugBarVarDumperTest.php', $d->renderVar($testData));

        $d->resetClonerOptions([
            'casters' => [],
        ]);
        $this->assertStringNotContainsString('DebugBarVarDumperTest.php', $d->renderVar($testData));

        // Test that the 'additional_casters' option can add new casters
        $testData = function(): void {};
        $d->resetClonerOptions();
        $this->assertStringContainsString('DebugBarVarDumperTest.php', $d->renderVar($testData));

        $d->resetClonerOptions([
            'casters' => [],
            'additional_casters' => ['Closure' => ReflectionCaster::class . '::castClosure'],
        ]);
        $this->assertStringContainsString('DebugBarVarDumperTest.php', $d->renderVar($testData));

        // Test 'max_items'
        $testData = [['one', 'two', 'three', 'four', 'five']];
        $d->resetClonerOptions();
        $out = $d->renderVar($testData);
        foreach ($testData[0] as $search) {
            $this->assertStringContainsString($search, $out);
        }

        $d->resetClonerOptions([
            'max_items' => 3,
        ]);
        $out = $d->renderVar($testData);
        $this->assertStringContainsString('one', $out);
        $this->assertStringContainsString('two', $out);
        $this->assertStringContainsString('three', $out);
        $this->assertStringNotContainsString('four', $out);
        $this->assertStringNotContainsString('five', $out);

        // Test 'max_string'
        $testData = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $d->resetClonerOptions();
        $this->assertStringContainsString($testData, $d->renderVar($testData));

        $d->resetClonerOptions([
            'max_string' => 10,
        ]);
        $out = $d->renderVar($testData);
        $this->assertStringContainsString('ABCDEFGHIJ', $out);
        $this->assertStringNotContainsString('ABCDEFGHIJK', $out);

        // Test 'min_depth' if we are on a Symfony version that supports it
        if (method_exists(AbstractCloner::class, 'setMinDepth')) {
            $testData = ['one', 'two', 'three', 'four', 'five'];
            $d->resetClonerOptions([
                'max_items' => 3,
            ]);
            $out = $d->renderVar($testData);
            foreach ($testData as $search) {
                $this->assertStringContainsString($search, $out);
            }

            $d->resetClonerOptions([
                'min_depth' => 0,
                'max_items' => 3,
            ]);
            $out = $d->renderVar($testData);
            $this->assertStringContainsString('one', $out);
            $this->assertStringContainsString('two', $out);
            $this->assertStringContainsString('three', $out);
            $this->assertStringNotContainsString('four', $out);
            $this->assertStringNotContainsString('five', $out);
        }
    }

    #[\ReturnTypeWillChange] public function testDumperOptions(): void
    {
        // Test the actual operation of the dumper options
        $d = new DebugBarVarDumper();

        // Test that the 'styles' option affects assets
        $d->resetDumperOptions();

        $assets = $d->getAssets();
        $this->assertStringNotContainsString(self::STYLE_STRING, $assets['inline_head']['html_var_dumper']);

        $d->resetDumperOptions(['styles' => $this->testStyles]);
        $assets = $d->getAssets();
        $this->assertStringContainsString(self::STYLE_STRING, $assets['inline_head']['html_var_dumper']);

        // The next tests require changes in Symfony 3.2:
        $dumpMethod = new \ReflectionMethod(HtmlDumper::class, 'dump');
        if ($dumpMethod->getNumberOfParameters() >= 3) {
            // Test that the 'expanded_depth' option affects output
            $d->resetDumperOptions(['expanded_depth' => 123321]);
            $out = $d->renderVar(true);
            $this->assertStringContainsString('123321', $out);

            // Test that the 'max_string' option affects output
            $d->resetDumperOptions(['max_string' => 321123]);
            $out = $d->renderVar(true);
            $this->assertStringContainsString('321123', $out);

            // Test that the 'file_link_format' option affects output
            $d->resetDumperOptions(['file_link_format' => 'fmt%ftest']);
            $out = $d->renderVar(function(): void {});
            $this->assertStringContainsString('DebugBarVarDumperTest.phptest', $out);
        }
    }
}
