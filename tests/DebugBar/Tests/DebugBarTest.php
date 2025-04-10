<?php

namespace DebugBar\Tests;

use DebugBar\DebugBarException;
use DebugBar\Tests\DataCollector\MockCollector;
use DebugBar\Tests\Storage\MockStorage;

class DebugBarTest extends DebugBarTestCase
{
    public $debugbar;
    #[\ReturnTypeWillChange] public function testAddCollector(): void
    {
        $this->debugbar->addCollector($c = new MockCollector());
        $this->assertTrue($this->debugbar->hasCollector('mock'));
        $this->assertEquals($c, $this->debugbar->getCollector('mock'));
        $this->assertContains($c, $this->debugbar->getCollectors());
    }

    #[\ReturnTypeWillChange] public function testAddCollectorWithSameName(): void
    {
        $this->debugbar->addCollector(new MockCollector());

        $this->expectException(DebugBarException::class);

        $this->debugbar->addCollector(new MockCollector());
    }

    #[\ReturnTypeWillChange] public function testCollect(): void
    {
        $data = ['foo' => 'bar'];
        $this->debugbar->addCollector(new MockCollector($data));
        $datac = $this->debugbar->collect();

        $this->assertArrayHasKey('mock', $datac);
        $this->assertEquals($datac['mock'], $data);
        $this->assertEquals($datac, $this->debugbar->getData());
    }

    #[\ReturnTypeWillChange] public function testArrayAccess(): void
    {
        $this->debugbar->addCollector($c = new MockCollector());
        $this->assertEquals($c, $this->debugbar['mock']);
        $this->assertTrue(isset($this->debugbar['mock']));
        $this->assertFalse(isset($this->debugbar['foo']));
    }

    #[\ReturnTypeWillChange] public function testStorage(): void
    {
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector(new MockCollector(['foo']));

        $data = $this->debugbar->collect();
        $this->assertEquals($s->data[$this->debugbar->getCurrentRequestId()], $data);
    }

    #[\ReturnTypeWillChange] public function testGetDataAsHeaders(): void
    {
        $this->debugbar->addCollector($c = new MockCollector(['foo']));
        $headers = $this->debugbar->getDataAsHeaders();
        $this->assertArrayHasKey('phpdebugbar', $headers);
    }

    #[\ReturnTypeWillChange] public function testSendDataInHeaders(): void
    {
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->addCollector($c = new MockCollector(['foo']));

        $this->debugbar->sendDataInHeaders();
        $this->assertArrayHasKey('phpdebugbar', $http->headers);
    }

    #[\ReturnTypeWillChange] public function testSendDataInHeadersWithOpenHandler(): void
    {
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector($c = new MockCollector(['foo']));

        $this->debugbar->sendDataInHeaders(true);
        $this->assertArrayHasKey('phpdebugbar-id', $http->headers);
        $this->assertEquals($this->debugbar->getCurrentRequestId(), $http->headers['phpdebugbar-id']);
    }

    #[\ReturnTypeWillChange] public function testStackedData(): void
    {
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->addCollector($c = new MockCollector(['foo']));
        $this->debugbar->stackData();

        $this->assertArrayHasKey($ns = $this->debugbar->getStackDataSessionNamespace(), $http->session);
        $this->assertArrayHasKey($id = $this->debugbar->getCurrentRequestId(), $http->session[$ns]);
        $this->assertArrayHasKey('mock', $http->session[$ns][$id]);
        $this->assertEquals($c->collect(), $http->session[$ns][$id]['mock']);
        $this->assertTrue($this->debugbar->hasStackedData());

        $data = $this->debugbar->getStackedData();
        $this->assertArrayNotHasKey($ns, $http->session);
        $this->assertArrayHasKey($id, $data);
        $this->assertEquals(1, count($data));
        $this->assertArrayHasKey('mock', $data[$id]);
        $this->assertEquals($c->collect(), $data[$id]['mock']);
    }

    #[\ReturnTypeWillChange] public function testStackedDataWithStorage(): void
    {
        $http = $this->debugbar->getHttpDriver();
        $this->debugbar->setStorage($s = new MockStorage());
        $this->debugbar->addCollector($c = new MockCollector(['foo']));
        $this->debugbar->stackData();

        $id = $this->debugbar->getCurrentRequestId();
        $this->assertNull($http->session[$this->debugbar->getStackDataSessionNamespace()][$id]);

        $data = $this->debugbar->getStackedData();
        $this->assertEquals($c->collect(), $data[$id]['mock']);
    }
}
