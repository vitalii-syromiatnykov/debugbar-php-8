<?php

namespace DebugBar\Tests\DataCollector;

use PHPUnit\Framework\MockObject\MockObject;
use DebugBar\Bridge\Propel2Collector;
use DebugBar\DataFormatter\DataFormatter;
use DebugBar\Tests\DebugBarTestCase;

class Propel2CollectorTest extends DebugBarTestCase
{
    /**
     * @var null|\PHPUnit_Framework_MockObject_MockObject
     */
    protected MockObject $stub = null;

    /* @var null|DataFormatter */
    protected $dataFormatter;

    #[\ReturnTypeWillChange]
    #[\Override] public function setUp(): void
    {
        $config = [
            'slowTreshold' => 0.1,
            'details' => [
                'time' => [
                    'name' => 'Time',
                    'precision' => 3,
                    'pad' => 8,
                ],
                'mem' => [
                    'name' => 'Memory',
                    'precision' => 3,
                    'pad' => 8,
                ],
                'memDelta' => [
                    'name' => 'Memory Delta',
                    'precision' => 3,
                    'pad' => 8,
                ],
                'memPeak' => [
                    'name' => 'Memory Peak',
                    'precision' => 3,
                    'pad' => 8,
                ],
            ],
            'innerGlue' => ': ',
            'outerGlue' => ' | ',
        ];

        $stub = $this->getMockBuilder(Propel2Collector::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDataFormatter', 'getHandler', 'getConfig'])
            ->getMock();

        $this->dataFormatter = new DataFormatter();

        $stub->method('getDataFormatter')->willReturn($this->dataFormatter);

        $stub->method('getConfig')->willReturn($config);

        $this->stub = $stub;
    }

    protected function equals($correctResult, $record)
    {
        $this->stub->method('getHandler')->willReturn(new MockHandler($record));
        $this->assertEquals($correctResult, $this->stub->collect());
    }

    #[\ReturnTypeWillChange] public function testSimpleMessage(): void
    {
        $record = [
            'message' => 'Simple message',
            'context' => 'propel',
            'level' => 200,
            'level_name' => 'INFO',
            'channel' => 'propel',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => [],
        ];

        $correctResult = [
            'nb_statements' => 0,
            'nb_failed_statements' => 0,
            'accumulated_duration' => 0,
            'accumulated_duration_str' => $this->dataFormatter->formatDuration(0),
            'memory_usage' => 0,
            'memory_usage_str' => $this->dataFormatter->formatBytes(0),
            'statements' =>  [
                [
                    'sql' => 'Simple message',
                    'is_success' => true,
                    'duration' => null,
                    'duration_str' => $this->dataFormatter->formatDuration(0),
                    'memory' => null,
                    'memory_str' => $this->dataFormatter->formatBytes(0),
                ],
            ]
        ];
        $this->equals($correctResult, [$record]);
    }

    #[\ReturnTypeWillChange] public function testErrorMessage(): void
    {
        $record = [
            'message' => 'Error message',
            'context' => 'propel',
            'level' => 500,
            'level_name' => 'critical',
            'channel' => 'propel',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => [],
        ];

        $correctResult = [
            'nb_statements' => 0,
            'nb_failed_statements' => 1,
            'accumulated_duration' => 0,
            'accumulated_duration_str' => $this->dataFormatter->formatDuration(0),
            'memory_usage' => 0,
            'memory_usage_str' => $this->dataFormatter->formatBytes(0),
            'statements' =>  [
                [
                    'sql' => '',
                    'is_success' => false,
                    'error_code' => 500,
                    'error_message' => 'Error message',
                    'duration' => null,
                    'duration_str' => $this->dataFormatter->formatDuration(0),
                    'memory' => null,
                    'memory_str' => $this->dataFormatter->formatBytes(0),
                ],
            ]
        ];

        $this->equals($correctResult, [$record]);
    }

    #[\ReturnTypeWillChange] public function testProfileMessage(): void
    {
        $record = [
            'message' => '     Time: 0.100ms | Memory:  1MB | Memory Delta: +1.0kB | Memory Peak:  2MB | SELECT id, first_name, last_name FROM author WHERE id = 1',
            'context' => 'propel',
            'level' => 200,
            'level_name' => 'info',
            'channel' => 'propel',
            'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
            'extra' => [],
        ];

        $correctResult = [
            'nb_statements' => 1,
            'nb_failed_statements' => 0,
            'accumulated_duration' => 0.0001,
            'accumulated_duration_str' => $this->dataFormatter->formatDuration(0.0001),
            'memory_usage' => 1024.0,
            'memory_usage_str' => $this->dataFormatter->formatBytes(1024.0),
            'statements' =>  [
                [
                    'sql' => 'SELECT id, first_name, last_name FROM author WHERE id = 1',
                    'is_success' => true,
                    'duration' => 0.0001,
                    'duration_str' => $this->dataFormatter->formatDuration(0.0001),
                    'memory' => 1024.0,
                    'memory_str' => $this->dataFormatter->formatBytes(1024.0),
                ],
            ]
        ];

        $this->equals($correctResult, [$record]);
    }

    #[\ReturnTypeWillChange] public function testSummaryProfileMessage(): void
    {
        $records = [
            [
                'message' => '     Time: 0.100ms | Memory:  1MB | Memory Delta: +1.0kB | Memory Peak:  2MB | SELECT id, first_name, last_name FROM author WHERE id = 1',
                'context' => 'propel',
                'level' => 200,
                'level_name' => 'info',
                'channel' => 'propel',
                'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
                'extra' => [],
            ],
            [
                'message' => '     Time: 0.100ms | Memory:  1MB | Memory Delta: +1.0kB | Memory Peak:  2MB | SELECT id, first_name, last_name FROM author WHERE id = 1',
                'context' => 'propel',
                'level' => 200,
                'level_name' => 'info',
                'channel' => 'propel',
                'datetime' => \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true))),
                'extra' => [],
            ],
        ];

        $correctResult = [
            'nb_statements' => 2,
            'nb_failed_statements' => 0,
            'accumulated_duration' => 0.0002,
            'accumulated_duration_str' => $this->dataFormatter->formatDuration(0.0002),
            'memory_usage' => 2048,
            'memory_usage_str' => $this->dataFormatter->formatBytes(2048),
            'statements' =>  [
                [
                    'sql' => 'SELECT id, first_name, last_name FROM author WHERE id = 1',
                    'is_success' => true,
                    'duration' => 0.0001,
                    'duration_str' => $this->dataFormatter->formatDuration(0.0001),
                    'memory' => 1024.0,
                    'memory_str' => $this->dataFormatter->formatBytes(1024.0),
                ],
                [
                    'sql' => 'SELECT id, first_name, last_name FROM author WHERE id = 1',
                    'is_success' => true,
                    'duration' => 0.0001,
                    'duration_str' => $this->dataFormatter->formatDuration(0.0001),
                    'memory' => 1024.0,
                    'memory_str' => $this->dataFormatter->formatBytes(1024.0),
                ],
            ]
        ];
        $this->equals($correctResult, $records);
    }
}

class MockHandler
{

    #[\ReturnTypeWillChange]
    public function __construct(protected $records)
    {
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange] public function getRecords()
    {
        return $this->records;
    }
}
