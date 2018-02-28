<?php

namespace App\Traits;

use Carbon\Carbon;

trait Profiler
{
    /**
     * Hold the groups for the profiler
     *
     * @array
     */
    protected $profiler_groups = [];

    /**
     * Calculate the average length of time for an operation
     *
     * @param $group
     * @return ProfilerTimer
     */
    public function profiler_avg($group)
    {
        if(!isset($this->profiler_groups[$group]))
            $this->profiler_groups[$group] = collect();

        $timer = new ProfilerTimer();
        $this->profiler_groups[$group]->push($timer);

        return $timer;
    }

    /**
     * Returns a collection of all the groups profiled
     *
     * @return array
     */
    public function get_profiler_groups() {
        return collect($this->profiler_groups)->map(function($group, $group_name) {
            $time_lengths = $group->map(function($timer) {
                return $timer->time_length;
            })->toArray();

            return ['name' => $group_name, 'avg_length' => array_sum($time_lengths) / count($time_lengths), 'total_length' => array_sum($time_lengths)];
        })->toArray();
    }
}

class ProfilerTimer {
    public $start_time;
    public $end_time;
    public $time_length;

    public function __construct()
    {
        $this->start_time = microtime(true);
    }

    public function stop() {
        $this->end_time = microtime(true);

        $this->time_length = $this->end_time - $this->start_time;
    }
}
