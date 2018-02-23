<?php
namespace App\Strategies;

use App\Models\AccountTrade;
use App\Models\ExchangeCandle;

/**
 * Based off the Turtle PDF
 *
 * @url http://www.metastocktools.com/downloads/turtlerules.pdf
 */
class Turtle extends Strategy {
    /**
     * Turtles either did a fast system 20 day or a 55 day breakout
     *
     * @var integer
     */
    protected $donchian_break_out_length = 20;

    /**
     * Turtles set exit breakout length to 10 or 20
     *
     * @var integer
     */
    protected $exit_breakout_length = 10;

    /**
     * Turtles set ATR length to 20
     *
     * @var integer
     */
    protected $atr_length = 20;

    /**
     * Max units in a single market
     */
    protected $max_units_per_market = 4;

    /**
     * This unit size is calculated for every candle
     *
     * @var float
     */
    protected $unit_size;

    /**
     * Offers ability to change the donchian breakout length
     *
     * @param int $length
     */
    public function setDonchianBreakoutLength($length = 55) {
        $this->donchian_break_out_length = $length;
    }

    /**
     * Offers ability to change the exit breakout length
     *
     * @param int $length
     */
    public function setExitBreakoutLength($length = 20) {
        $this->exit_breakout_length = $length;
    }

    /**
     * Offers ability to change ATR length
     *
     * @param int $length
     */
    public function setATRLength($length = 20) {
        $this->atr_length = $length;
    }

    /**
     * Ability to change how many units per market
     *
     * @param int $units
     */
    public function setMaxUnitsPerMarket($units = 4) {
        $this->max_units_per_market = $units;
    }

    /**
     * @param ExchangeCandle $candle
     */
    protected function executeStrategy(ExchangeCandle $candle) {
        $n = $this->indicators->atr($this->atr_length);
        $this->unit_size = $this->account->getAccountSize() / ($n / $candle->close);

        $this->enter($candle);
        $this->exit($candle);
    }

    /**
     * Enter into a market
     *
     * @param ExchangeCandle $candle
     */
    protected function enter(ExchangeCandle $candle) {
        $highest = $this->highest($this->donchian_break_out_length);
        $lowest = $this->lowest($this->donchian_break_out_length);

        // Long check
        if($candle->high > $highest) {
            dd('LONG');
            $this->buy(AccountTrade::POSITION_LONG);
        }

        // Short check
        if($candle->low < $lowest) {
            // What can we do to recreate this
            $recreate = [
                'low' => $candle->low,
                'lowest' => $lowest,
                'donchian_break_out_length' => $this->donchian_break_out_length,
            ];

            $this->buy($candle, AccountTrade::POSITION_SHORT, 'Donchian channels indicate a short', $recreate);
        }
    }

    /**
     * Enter into a market
     *
     * @param ExchangeCandle $candle
     */
    protected function exit(ExchangeCandle $candle) {
        $highest = $this->highest($this->exit_breakout_length);
        $lowest = $this->lowest($this->exit_breakout_length);

        // Long check
        if($candle->low <= $lowest) {
            // Get open long orders
            // $open_orders = $this->trader->getOpenLongOrders()->each(function($order) {$order->close()}
        }

        // Short check
        if($candle->high > $highest) {
            // Get open short orders
            // $short_orders = $this->trader->getOpenShortOrders()->each(function($order) {$order->close()}
        }
    }


    /**
     * Perform an actual trade
     *
     * @param ExchangeCandle $candle
     * @param $side
     * @param $position
     * @param $type
     * @param $reason
     * @param $recreate
     * @return mixed
     */
    protected function trade(ExchangeCandle $candle, $side, $position, $type, $reason, $recreate) {
        return $this->trader->trade($candle, $type, $position, $side, $this->unit_size, $reason, $recreate);
    }

    /********** ALIASES **********/

    /**
     * Performs a buy
     *
     * @param ExchangeCandle $candle
     * @param $position
     * @param $reason
     * @param $recreate
     * @return bool|mixed
     */
    protected function buy(ExchangeCandle $candle, $position, $reason, $recreate) {
        // Need to create stop orders...
        $units = $this->trader->getUnitsForMarket($this->currency, $this->asset);

        if($units >= $this->max_units_per_market) {
            // Log something here
            return false;
        }

        return $this->trade($candle, AccountTrade::SIDE_BUY, $position, AccountTrade::TYPE_LIMIT, $reason, $recreate);
    }

    /**
     * Performs a sell
     *
     * @param ExchangeCandle $candle
     * @param $position
     * @param $reason
     * @param $recreate
     * @return mixed
     */
    protected function sell(ExchangeCandle $candle, $position, $reason, $recreate) {
        // Need to create limit orders...

        return $this->trade($candle, AccountTrade::SIDE_SELL, $position, AccountTrade::TYPE_LIMIT, $reason, $recreate);
    }
}