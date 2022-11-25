<?php

namespace DebugBar\Tests;

use DebugBar\DebugBar;
use DebugBar\RandomRequestIdGenerator;
use PHPUnit\Framework\TestCase;

abstract class DebugBarTestCase extends TestCase
{
    #[\ReturnTypeWillChange] public function setUp(): void
    {
        $this->debugbar = new DebugBar();
        $this->debugbar->setHttpDriver($http = new MockHttpDriver());
    }

    #[\ReturnTypeWillChange] public function assertJsonIsArray($json)
    {
        $data = json_decode($json);
        $this->assertTrue(is_array($data));
    }

    #[\ReturnTypeWillChange] public function assertJsonIsObject($json)
    {
        $data = json_decode($json);
        $this->assertTrue(is_object($data));
    }

    #[\ReturnTypeWillChange] public function assertJsonArrayNotEmpty($json)
    {
        $data = json_decode($json, true);
        $this->assertTrue(is_array($data) && !empty($data));
    }

    #[\ReturnTypeWillChange] public function assertJsonHasProperty($json, $property)
    {
        $data = json_decode($json, true);
        $this->assertTrue(array_key_exists($property, $data));
    }

    #[\ReturnTypeWillChange] public function assertJsonPropertyEquals($json, $property, $expected)
    {
        $data = json_decode($json, true);
        $this->assertTrue(array_key_exists($property, $data));
        $this->assertEquals($expected, $data[$property]);
    }
}
