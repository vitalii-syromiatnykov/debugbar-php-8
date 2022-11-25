<?php

namespace DebugBar\Tests\DataCollector;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

class MockCollector extends DataCollector implements Renderable
{
    protected $data;
    protected $name;
    protected $widgets;

    #[\ReturnTypeWillChange] public function __construct($data = array(), $name = 'mock', $widgets = array())
    {
        $this->data = $data;
        $this->name = $name;
        $this->widgets = $widgets;
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
