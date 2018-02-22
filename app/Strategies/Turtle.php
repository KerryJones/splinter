<?php
namespace App\Strategies;

use App\Models\ExchangeCandle;

class Turtle extends Strategy {

    /**
     * @param ExchangeCandle $candle
     */
    protected function executeStrategy(ExchangeCandle $candle) {
        dd($candle->high > $this->highest(20) || $candle->high > $this->highest(55));
    }
}