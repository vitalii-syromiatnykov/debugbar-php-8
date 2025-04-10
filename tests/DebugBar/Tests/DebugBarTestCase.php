<?php

namespace DebugBar\Tests;

use DebugBar\DebugBar;
use PHPUnit\Framework\TestCase;

abstract class DebugBarTestCase extends TestCase
{
    public $debugbar;
    #[\ReturnTypeWillChange]protected function setUp(): void
    {
        $this->debugbar = new DebugBar();
        $this->debugbar->setHttpDriver($http = new MockHttpDriver());
    }

    #[\ReturnTypeWillChange] public function assertJsonIsArray($json): void
    {
        $data = json_decode((string) $json);
        $this->assertTrue(is_array($data));
    }

    #[\ReturnTypeWillChange] public function assertJsonIsObject($json): void
    {
        $data = json_decode((string) $json);
        $this->assertTrue(is_object($data));
    }

    #[\ReturnTypeWillChange] public function assertJsonArrayNotEmpty($json): void
    {
        $data = json_decode((string) $json, true);
        $this->assertTrue(is_array($data) && $data !== []);
    }

    #[\ReturnTypeWillChange] public function assertJsonHasProperty($json, $property): void
    {
        $data = json_decode((string) $json, true);
        $this->assertTrue(array_key_exists($property, $data));
    }

    #[\ReturnTypeWillChange] public function assertJsonPropertyEquals($json, $property, $expected): void
    {
        $data = json_decode((string) $json, true);
        $this->assertTrue(array_key_exists($property, $data));
        $this->assertEquals($expected, $data[$property]);
    }
}
