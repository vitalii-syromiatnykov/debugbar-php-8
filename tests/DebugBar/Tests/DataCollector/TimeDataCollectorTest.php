<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\Tests\DebugBarTestCase;
use DebugBar\DataCollector\TimeDataCollector;

class TimeDataCollectorTest extends DebugBarTestCase
{
    public $s;
    public $c;
    #[\ReturnTypeWillChange]
    #[\Override] public function setUp(): void
    {
        $this->s = microtime(true);
        $this->c = new TimeDataCollector($this->s);
    }

    #[\ReturnTypeWillChange] public function testAddMeasure(): void
    {
        $this->c->addMeasure('foo', $this->s, $this->s + 10, ['a' => 'b'], 'timer');
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('foo', $m[0]['label']);
        $this->assertEquals(10, $m[0]['duration']);
        $this->assertEquals(['a' => 'b'], $m[0]['params']);
        $this->assertEquals('timer', $m[0]['collector']);
    }

    #[\ReturnTypeWillChange] public function testStartStopMeasure(): void
    {
        $this->c->startMeasure('foo', 'bar', 'baz');
        usleep(1000);
        $this->c->stopMeasure('foo', ['bar' => 'baz']);
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('bar', $m[0]['label']);
        $this->assertEquals('baz', $m[0]['collector']);
        $this->assertEquals(['bar' => 'baz'], $m[0]['params']);
        $this->assertTrue($m[0]['start'] < $m[0]['end']);
    }

    #[\ReturnTypeWillChange] public function testCollect(): void
    {
        $this->c->addMeasure('foo', 0, 10);
        $this->c->addMeasure('bar', 10, 20);

        $data = $this->c->collect();
        $this->assertTrue($data['end'] > $this->s);
        $this->assertTrue($data['duration'] > 0);
        $this->assertCount(2, $data['measures']);
    }

    #[\ReturnTypeWillChange] public function testMeasure(): void
    {
        $returned = $this->c->measure('bar', fn(): string => 'returnedValue');
        $m = $this->c->getMeasures();
        $this->assertCount(1, $m);
        $this->assertEquals('bar', $m[0]['label']);
        $this->assertTrue($m[0]['start'] < $m[0]['end']);
        $this->assertSame('returnedValue', $returned);
    }
}
