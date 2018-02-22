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
     * Offers ability to change this
     *
     * @param int $length
     */
    public function setATRLength($length = 20) {
        $this->atr_length = $length;
    }

    /**
     * @param ExchangeCandle $candle
     */
    protected function executeStrategy(ExchangeCandle $candle) {
        $n = $this->indicators->atr($this->atr_length);
        $this->unit_size = $this->trader->account->account_size / ($n / $candle->close);

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
            $this->buy(AccountTrade::POSITION_LONG);
        }

        // Short check
        if($candle->low < $lowest) {
            $this->buy(AccountTrade::POSITION_SHORT);
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


    protected function trade($side, $position) {
        // $this->trader->trade(...)
    }

    /********** ALIASES ***********/
    protected function buy($position) {
        // Need to create stop orders...
        // $this->trader->trade
        return $this->trade(AccountTrade::SIDE_BUY, $position);
    }

    protected function sell($position) {
        // Need to create limit orders...

        return $this->trade(AccountTrade::SIDE_SELL, $position);
    }
}