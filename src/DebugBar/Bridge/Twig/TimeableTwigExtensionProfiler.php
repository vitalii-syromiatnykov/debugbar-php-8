<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2017 Tim Riemenschneider
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Bridge\Twig;

use DebugBar\DataCollector\TimeDataCollector;
use Twig_Profiler_Profile;

/**
 * Class TimeableTwigExtensionProfiler
 *
 * Extends Twig_Extension_Profiler to add rendering times to the TimeDataCollector
 *
 * @package DebugBar\Bridge\Twig
 */
class TimeableTwigExtensionProfiler extends \Twig_Extension_Profiler
{
    #[\ReturnTypeWillChange] public function setTimeDataCollector(TimeDataCollector $timeDataCollector): void
    {
        $this->timeDataCollector = $timeDataCollector;
    }

    #[\ReturnTypeWillChange] public function __construct(\Twig_Profiler_Profile $profile, private ?TimeDataCollector $timeDataCollector = null)
    {
        parent::__construct($profile);
    }

    #[\ReturnTypeWillChange] public function enter(Twig_Profiler_Profile $profile): void
    {
        if ($this->timeDataCollector && $profile->isTemplate()) {
            $this->timeDataCollector->startMeasure($profile->getName(), 'template ' . $profile->getName());
        }

        parent::enter($profile);
    }

    #[\ReturnTypeWillChange] public function leave(Twig_Profiler_Profile $profile): void
    {
        parent::leave($profile);
        if ($this->timeDataCollector && $profile->isTemplate()) {
            $this->timeDataCollector->stopMeasure($profile->getName());
        }
    }
}
