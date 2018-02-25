<?php
namespace App\Strategies;

use App\Models\AccountTrade;
use App\Models\ExchangeCandle;
use ccxt\Exchange;
use Illuminate\Database\Eloquent\Collection;
use Webpatser\Uuid\Uuid;

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
     *
     * @var integer
     */
    protected $max_units_per_market = 4;

    /**
     * The % of total account size to use in unit size calculation
     *
     * @var integer
     */
    protected $unit_size_account_percent = 1;

    /**
     * This unit size is calculated for every candle
     *
     * @var float
     */
    protected $unit_size;

    /**
     * This is the last open long order, if there is one
     *
     * @var AccountTrade
     */
    protected $last_open_long_order;

    /**
     * This is the last open short order, if there is one
     *
     * @var AccountTrade
     */
    protected $last_open_short_order;

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
     * Ability to change how many units per market
     *
     * @param int $percent
     */
    public function setUnitSizeAccountPercentage($percent = 1) {
        $this->unit_size_account_percent = $percent;
    }

    /**
     * @param ExchangeCandle $candle
     * @throws \Exception
     */
    protected function executeStrategy(ExchangeCandle $candle) {
        $atr = $this->indicators->atr($this->atr_length);
        $this->unit_size = $this->calculateUnitSize($atr, $candle);

        // Get last open orders
        $this->last_open_long_order = $this->getLastLongOrder();
        $this->last_open_short_order = $this->getLastShortOrder();

        $this->exit($candle, ['atr' => $atr]);
        $this->enter($candle, ['atr' => $atr]);
    }

    /**
     * Enter into a market
     *
     * @param ExchangeCandle $candle
     * @param array $indicators
     * @throws \Exception
     */
    protected function enter(ExchangeCandle $candle, $indicators = []) {
        // Long check
        if($this->last_open_long_order) {
            // Do we increment? increase by 0.5(atr)
            if($candle->close >= $this->last_open_long_order->currency_per_asset + (0.5 * $this->last_open_long_order->recreate->atr)) {
                $recreate = ['atr' => $this->last_open_long_order->recreate->atr];
                $group_id = $this->last_open_long_order->group_id;

                $this->buy($candle, AccountTrade::POSITION_LONG, 'Market increased by 0.5 * n over last order', $recreate, $group_id);
            }

        } else {
            // We can do a new order!
            $highest = $this->highest($this->donchian_break_out_length);

            if($candle->high > $highest) {
                // What can we do to recreate this
                $recreate = $indicators + [
                    'high' => $candle->high,
                    'highest' => $highest,
                    'donchian_break_out_length' => $this->donchian_break_out_length,
                ];

                $group_id = Uuid::generate()->string;

                $this->buy($candle, AccountTrade::POSITION_LONG, 'Donchian channels indicate a long', $recreate, $group_id);
            }
        }


        // Short check
        if($this->last_open_short_order) {
            // Do we increment
            if($candle->close <= $this->last_open_short_order->currency_per_asset - (0.5 * $this->last_open_short_order->recreate->atr)) {
                $recreate = ['atr' => $this->last_open_short_order->recreate->atr];
                $group_id = $this->last_open_short_order->group_id;

                $this->buy($candle, AccountTrade::POSITION_SHORT, 'Market decreased by 0.5 * n over last order', $recreate, $group_id);
            }
        } else {
            $lowest = $this->lowest($this->donchian_break_out_length);

            if($candle->low < $lowest) {
                // What can we do to recreate this
                $recreate = $indicators + [
                    'low' => $candle->low,
                    'lowest' => $lowest,
                    'donchian_break_out_length' => $this->donchian_break_out_length,
                ];

                $group_id = Uuid::generate()->string;

                $this->buy($candle, AccountTrade::POSITION_SHORT, 'Donchian channels indicate a short', $recreate, $group_id);
            }
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
        if($this->last_open_long_order && $candle->low <= $lowest) {
            $reason = $this->exit_breakout_length . ' day low signals to sell longs';
            $recreate = [
                'exit_breakout_length' => $this->exit_breakout_length,
                'lowest' => $lowest,
                'low' => $candle->low
            ];

            $this->sell($candle, $this->getOpenLongOrders(), AccountTrade::POSITION_LONG, $reason, $recreate, $this->last_open_long_order->group_id);
        }

        // Short check
        if($this->last_open_short_order && $candle->high > $highest) {
            $reason = $this->exit_breakout_length . ' day low signals to sell shorts';
            $recreate = [
                'exit_breakout_length' => $this->exit_breakout_length,
                'highest' => $highest,
                'high' => $candle->high
            ];

            $this->sell($candle, $this->getOpenShortOrders(), AccountTrade::POSITION_SHORT, $reason, $recreate, $this->last_open_short_order->group_id);
        }
    }


    /**
     * Perform an actual trade
     *
     * @param ExchangeCandle $candle
     * @param float $amount
     * @param string $side
     * @param string $position
     * @param string $type
     * @param string $reason
     * @param array $recreate
     * @param string $group_id
     * @return mixed
     */
    protected function trade(ExchangeCandle $candle, $amount, $side, $position, $type, $reason, array $recreate, $group_id) {
        return $this->trader->trade($candle, $this->currency, $this->asset, $type, $position, $side, $amount, $reason, $recreate, $group_id);
    }

    /**
     * @param $n
     * @param ExchangeCandle $candle
     * @return float|int
     */
    protected function calculateUnitSize($n, ExchangeCandle $candle) {
        return ($this->unit_size_account_percent * .001) * $this->account->getAccountSize() / ($n / $candle->close);
    }

    /*       ***                          ***        */
    /******************** ALIASES ********************/
    /*       ***                          ***        */

    /**
     * Performs a buy
     *
     * @param ExchangeCandle $candle
     * @param $position
     * @param $reason
     * @param $recreate
     * @return bool|mixed
     */
    protected function buy(ExchangeCandle $candle, $position, $reason, $recreate, $group_id) {
        // Need to create stop orders...
        $units = $this->trader->getUnitsForMarket($this->currency, $this->asset);

        if($units >= $this->max_units_per_market) {
            $this->console->writeln("\n<comment>Cannot buy {$position} position in the market. Already have " . $units . ' units in play. Max units: #' . $this->max_units_per_market . "</comment>");
            // Log something here
            return false;
        } else {
            $this->console->writeln("\n<fg=cyan>Buying $" . number_format($this->unit_size) . ' worth of ' . $this->asset . '</>');
        }

        return $this->trade($candle, $this->unit_size, AccountTrade::SIDE_BUY, $position, AccountTrade::TYPE_LIMIT, $reason, $recreate, $group_id);
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
    protected function sell(ExchangeCandle $candle, $orders, $position, $reason, $recreate, $group_id) {
        // How much are we selling? (All of it)
        $amount = $orders->reduce(function($amount, $order) {
            return $amount + $order->asset_size;
        }, 0);

        $this->console->writeln("\n<fg=cyan>Selling " . number_format($amount, 8) . ' of ' . $this->asset . '</>');


        return $this->trade($candle, $amount, AccountTrade::SIDE_SELL, $position, AccountTrade::TYPE_LIMIT, $reason, $recreate, $group_id);
    }

    /**
     * Get all open long orders
     *
     * @return AccountTrade[]
     */
    public function getOpenLongOrders() {
        return $this->trader->getOpenOrdersForPosition($this->currency, $this->asset, AccountTrade::POSITION_LONG);
    }

    /**
     * Get all open short orders
     *
     * @return AccountTrade
     */
    public function getOpenShortOrders() {
        return $this->trader->getOpenOrdersForPosition($this->currency, $this->asset, AccountTrade::POSITION_SHORT);
    }

    /**
     * Get first open long order
     *
     * @return AccountTrade
     */
    public function getFirstLongOrder() {
        return $this->trader->getFirstOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_LONG);
    }

    /**
     * Get first open short order
     *
     * @return AccountTrade
     */
    public function getFirstShortOrder() {
        return $this->trader->getFirstOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_SHORT);
    }

    /**
     * Get last open long order
     *
     * @return AccountTrade
     */
    public function getLastLongOrder() {
        return $this->trader->getLastOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_LONG);
    }

    /**
     * Get first open short order
     *
     * @return AccountTrade
     */
    public function getLastShortOrder() {
        return $this->trader->getLastOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_SHORT);
    }
}