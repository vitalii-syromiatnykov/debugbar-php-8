<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class MockCollector extends DataCollector implements Renderable
{
    #[\ReturnTypeWillChange]
    public function __construct(protected $data = [], protected $name = 'mock', protected $widgets = [])
    {
    }

    #[\ReturnTypeWillChange] public function collect()
    {
        return $this->data;
    }

    #[\ReturnTypeWillChange] public function getName()
    {
        return $this->name;
    }

    #[\ReturnTypeWillChange] public function getWidgets()
    {
        return $this->widgets;
    }
}
