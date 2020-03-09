<?php

namespace Search\Support;

class Performance
{
    private $measuresPerformance = [];

    public function __construct($memoryRealUsage = false)
    {
        $this->measuresPerformance = [
            'memoryRealUsage' => $memoryRealUsage,
            'memory' => null,
            'timer' => null,
        ];
    }

    public function start()
    {
        $this->measuresPerformance['memory'] = memory_get_peak_usage($this->measuresPerformance['memoryRealUsage']);
        $this->measuresPerformance['timer'] = microtime(true);
    }

    public function get()
    {
        $time = microtime(true) - $this->measuresPerformance['timer'];
        $memory = memory_get_peak_usage($this->measuresPerformance['memoryRealUsage']) - $this->measuresPerformance['memory'];

        return [
            'raw' => [
                'execution_time' => $time,
                'memory_usage' => $memory,
            ],
            'formatted' => [
                'execution_time' => round($time, 7) * 1000 .' ms',
                'memory_usage' => round(($memory / 1024 / 1024), 2) . 'MiB',
            ],
        ];
    }
}
