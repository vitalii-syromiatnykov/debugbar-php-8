<?php

namespace DebugBar\Tests;

use DebugBar\DebugBarException;
use DebugBar\OpenHandler;
use DebugBar\Tests\Storage\MockStorage;

class OpenHandlerTest extends DebugBarTestCase
{
    public $debugbar;
    public $openHandler;
    #[\ReturnTypeWillChange]
    #[\Override] public function setUp(): void
    {
        parent::setUp();
        $this->debugbar->setStorage(new MockStorage(['foo' => ['__meta' => ['id' => 'foo']]]));
        $this->openHandler = new OpenHandler($this->debugbar);
    }

    #[\ReturnTypeWillChange] public function testFind(): void
    {
        $request = [];
        $result = $this->openHandler->handle($request, false, false);
        $this->assertJsonArrayNotEmpty($result);
    }

    #[\ReturnTypeWillChange] public function testGet(): void
    {
        $request = ['op' => 'get', 'id' => 'foo'];
        $result = $this->openHandler->handle($request, false, false);
        $this->assertJsonIsObject($result);
        $this->assertJsonHasProperty($result, '__meta');
        $data = json_decode((string) $result, true);
        $this->assertEquals('foo', $data['__meta']['id']);
    }

    #[\ReturnTypeWillChange] public function testGetMissingId(): void
    {
        $this->expectException(DebugBarException::class);

        $this->openHandler->handle(['op' => 'get'], false, false);
    }

    #[\ReturnTypeWillChange] public function testClear(): void
    {
        $result = $this->openHandler->handle(['op' => 'clear'], false, false);
        $this->assertJsonPropertyEquals($result, 'success', true);
    }
}
