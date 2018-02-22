<?php

namespace App\Libraries;

use App\Models\ExchangeCandle;
use App\Strategies\Strategy;
use Illuminate\Database\Eloquent\Collection;

class Math
{
    public static function avg(array $array) {
        return array_sum($array) / count($array);
    }
}