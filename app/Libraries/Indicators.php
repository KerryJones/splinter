<?php

namespace App\Libraries;

use App\Models\ExchangeCandle;
use App\Strategies\Strategy;
use Illuminate\Database\Eloquent\Collection;

class Indicators
{
    /**
     * Holds the strategy in case we need to backfill
     *
     * @var Strategy
     */
    protected $strategy;

    /**
     * Holds all the candles that have been imported
     *
     * @var array|ExchangeCandle[]
     */
    protected $candles = [];

    /**
     * Holds the current position of the candle
     *
     * @var integer
     */
    private $candle_index = 0;

    /**
     * Holds the candle offset
     *
     * @var integer
     */
    private $candle_offset = 0;

    public function __construct(Strategy $strategy, Collection $candles) {
        $this->strategy = $strategy;
        $this->candles = $candles;
    }

    /**
     * Sets the index of current candle
     *
     * @param $index
     */
    public function setCandleIndex($index) {
        $this->candle_index = $index;
    }

    /********** OTHER FUNCTIONS **********/

    /**
     * Alias for Indicators::getRange('open', $length)
     *
     * @param $length
     * @return array
     */
    protected function open($length) {
        return $this->getRange('open', $length);
    }

    /**
     * Alias for Indicators::getRange('high', $length)
     *
     * @param $length
     * @return array
     */
    protected function high($length) {
        return $this->getRange('high', $length);
    }

    /**
     * Alias for Indicators::getRange('low', $length)
     *
     * @param $length
     * @return array
     */
    protected function low($length) {
        return $this->getRange('low', $length);
    }

    /**
     * Alias for Indicators::getRange('close', $length)
     *
     * @param $length
     * @return array
     */
    protected function close($length) {
        return $this->getRange('close', $length);
    }

    /**
     * Gets an array of the last n attribute
     *
     * @param $attribute
     * @param $length
     * @return array
     */
    protected function getRange($attribute, $length) {
        return $this->getSlicedCandles($length)->pluck($attribute)->toArray();
    }

    /**
     * Gets the highest closing value of the last n candles
     *
     * @param $length
     * @return float
     */
    public function highest($length) {
        return $this->getSlicedCandles($length)->max('close');
    }

    /**
     * Get's the highest closing value of the last n candles
     *
     * @param $length
     * @return float
     */
    public function lowest($length) {
        return $this->getSlicedCandles($length)->min('close');
    }

    /**
     * Gets a slice of candles going backwards
     *
     * @param $length
     * @return mixed
     */
    public function getSlicedCandles($length) {
        $index = $this->getCandleIndex($length);

        return $this->candles->slice($index - $length, $index);
    }

    /**
     * If we have to offset, lets mark it
     *
     * @return int
     */
    protected function getCandleIndex($length = null) {
        $index = $this->candle_index + $this->candle_offset;

        // We might need to backfill the candles
        if(!is_null($length) && $index < $length) {
            $this->candle_offset += $length - $index;
            $this->candles = $this->strategy->backfill($this->candles, $this->candle_offset);

            $index = $this->candle_index + $this->candle_offset;
        }

        return $index;
    }

    /********** INDICATORS **********/

    /**
     * Get the Average True Range (ATR) for a length
     *
     * @param $length
     * @return mixed
     */
    public function atr($length) {
        // It does 1 less
        $length++;

        $atr = trader_atr($this->high($length), $this->low($length), $this->close($length), $length-1);

        return array_pop($atr);
    }

    /**
     * Get the true range for a length
     *
     * @param $length
     * @return mixed
     */
    public function tr($length) {
        $tr = trader_trange($this->high($length), $this->low($length), $this->close($length));

        return array_pop($tr);
    }
}