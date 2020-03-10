<?php

namespace Search\Searching;

class SearchResult
{
    private $ids = [];
    private $scores = [];

    private $stats = [];
    private $totalHits = 0;

    public function __construct()
    {
        $this->stats = [
            'raw' => [
                'execution_time' => 0,
                'memory_usage' => 0,
            ],
            'formatted' => [
                'execution_time' => '',
                'memory_usage' => '',
            ],
        ];
        $this->totalHits = 0;
    }

    public function setIds(array $ids)
    {
        $this->ids = $ids;
    }

    public function setScores(array $scores)
    {
        $this->scores = $scores;
    }

    public function setStats(array $stats)
    {
        $this->stats = $stats;
    }

    public function setTotalHits($totalHits)
    {
        $this->totalHits = $totalHits;
    }

    public function get()
    {
        return [
            'ids' => $this->ids,
            'scores' => $this->scores,
            'total_hits' => $this->totalHits,
            'stats' => $this->stats,
        ];
    }
}
