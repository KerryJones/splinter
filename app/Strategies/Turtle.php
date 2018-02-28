<?php
namespace App\Strategies;

use App\Models\AccountTrade;
use App\Models\ExchangeCandle;
use Carbon\Carbon;
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
     * Record variables
     * 
     * @param array
     */
    protected $record_variables = [
        'donchian_breakout_length',
        'exit_breakout_length',
        'atr_length',
        'max_units_per_market',
        'unit_size_account_percent',
        'atr_multiplier',
        'short',
        'pyramid',
    ];
    
    /**
     * Give tons of alerts
     *
     * @var bool
     */
    protected $verbose = false;
    
    /**
     * Do we pyramid our orders
     *
     * @var bool
     */
    protected $pyramid = true;  
    
    /**
     * Do we short
     *
     * @var bool
     */
    protected $short = true;

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
     * Turtles set ATR multiplier to 2
     *
     * @var float
     */
    protected $atr_multiplier = 2;
    
    /**
     * Turtles set pyramid ATR multiplier to 0.5
     *
     * @var float
     */
    protected $atr_pyramid_multiplier = 0.5;

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
     * Offers ability to change the verbosity
     *
     * @param bool $verbose
     */
    public function setVerbose($verbose = true) {
        $this->verbose = $verbose;
    }
    /**
     * Offers ability to change us pyramiding orders
     *
     * @param bool $pyramid
     */
    public function setPyramid($pyramid = true) {
        $this->pyramid = $pyramid;
    }
    /**
     * Offers ability to change whether we short the market
     *
     * @param bool $short
     */
    public function setShort($short = true) {
        $this->short = $short;
    }

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
     * Offers ability to change ATR multiplier
     *
     * @param int $multiplier
     */
    public function setATRMultiplier($multiplier = 2) {
        $this->atr_multiplier = $multiplier;
    }

    /**
     * Offers ability to change ATR pyramid multiplier
     *
     * @param float $multiplier
     */
    public function setATRPyramidMultiplier($multiplier = 0.5) {
        $this->atr_pyramid_multiplier = $multiplier;
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

        // Exit first so that exits don't get trigger for enters
        $this->checkStops($candle);
        $this->exit($candle);
        $this->enter($candle, ['atr' => $atr]);
    }

    /**
     * Check to see if there are any stops and fulfill then
     *
     * @param ExchangeCandle $candle
     */
    protected function checkStops(ExchangeCandle $candle) {
        $stops = $this->trader->getOpenStops($this->currency, $this->asset);

        if($stops['long'] && $stops['long']->currency_per_asset >= $candle->close) {
            $this->log('Filling stop orders for long position at ' . number_format($candle->close, 8), 'red');
            $this->trader->fillOrder($stops['long'], $candle);
            $this->last_open_long_order = $this->getLastLongOrder();
        }

        if($stops['short'] && $stops['short']->currency_per_asset <= $candle->close) {
            $this->log('Filling stop orders for short position at ' . number_format($candle->close, 8), 'red');
            $this->trader->fillOrder($stops['short'], $candle);
            $this->last_open_short_order = $this->getLastShortOrder();
        }
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
        if($this->pyramid && $this->last_open_long_order) {
            $this->pyramidOrder($candle, AccountTrade::POSITION_LONG, $indicators);
        } else {
            // We can do a new order!
            $highest = $this->highest($this->donchian_break_out_length);

            if($candle->close > $highest) {
                // What can we do to recreate this
                $recreate = $indicators + [
                    'close' => $candle->close,
                    'highest' => $highest,
                    'donchian_break_out_length' => $this->donchian_break_out_length,
                ];

                $group_id = Uuid::generate()->string;

                // Buy
                $trade = $this->buy($candle, AccountTrade::POSITION_LONG, 'Donchian channels indicate a long', $recreate, $group_id);

                // Create Stop Loss
                if($trade)
                    $this->stopLoss($candle, $trade, 'Created a new group, setting stop-loss', ['atr' => $trade->recreate->atr]);
            }
        }

        // Short check
        if($this->short) {
            if($this->pyramid && $this->last_open_short_order) {
                $this->pyramidOrder($candle, AccountTrade::POSITION_SHORT, $indicators);
            } else {
                $lowest = $this->lowest($this->donchian_break_out_length);
    
                if($candle->close < $lowest) {
                    // What can we do to recreate this
                    $recreate = $indicators + [
                        'close' => $candle->close,
                        'lowest' => $lowest,
                        'donchian_break_out_length' => $this->donchian_break_out_length,
                    ];
    
                    $group_id = Uuid::generate()->string;
    
                    $trade = $this->buy($candle, AccountTrade::POSITION_SHORT, 'Donchian channels indicate a short', $recreate, $group_id);
    
                    // Create Stop Loss
                    if($trade)
                        $this->stopLoss($candle, $trade, 'Created a new group, setting stop-loss', ['atr' => $trade->recreate->atr]);
                }
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
        if($this->last_open_long_order && $candle->close <= $lowest) {
            $reason = $this->exit_breakout_length . ' day low signals to sell longs';
            $recreate = [
                'exit_breakout_length' => $this->exit_breakout_length,
                'lowest' => $lowest,
                'close' => $candle->close
            ];

            $this->sell($candle, $this->getOpenLongOrders(), AccountTrade::POSITION_LONG, $reason, $recreate, $this->last_open_long_order->group_id);
        }

        // Short check
        if($this->last_open_short_order && $candle->close >= $highest) {
            $reason = $this->exit_breakout_length . ' day high signals to sell shorts';
            $recreate = [
                'exit_breakout_length' => $this->exit_breakout_length,
                'highest' => $highest,
                'close' => $candle->close
            ];

            $this->sell($candle, $this->getOpenShortOrders(), AccountTrade::POSITION_SHORT, $reason, $recreate, $this->last_open_short_order->group_id);
        }
    }

    /**
     * Pyramid an existing order
     *
     * @param ExchangeCandle $candle
     * @param $position
     * @param array $indicators
     * @return AccountTrade|bool
     */
    protected function pyramidOrder(ExchangeCandle $candle, $position, $indicators = []) {
        // Do we increment?
        if($position == AccountTrade::POSITION_LONG && $candle->close >= $this->last_open_long_order->currency_per_asset + ($this->atr_pyramid_multiplier * $this->last_open_long_order->recreate->atr)) {
            $recreate = ['atr' => $this->last_open_long_order->recreate->atr];
            $group_id = $this->last_open_long_order->group_id;

            $trade = $this->buy($candle, AccountTrade::POSITION_LONG, "Market increased by {$this->atr_pyramid_multiplier} * n over last order", $recreate, $group_id);
        }

        // Do we increment
        if($position == AccountTrade::POSITION_SHORT && $candle->close <= $this->last_open_short_order->currency_per_asset - ($this->atr_pyramid_multiplier * $this->last_open_short_order->recreate->atr)) {
            $recreate = ['atr' => $this->last_open_short_order->recreate->atr];
            $group_id = $this->last_open_short_order->group_id;

            $trade = $this->buy($candle, AccountTrade::POSITION_SHORT, "Market decreased by {$this->atr_pyramid_multiplier} * n over last order", $recreate, $group_id);
        }

        if(isset($trade) && isset($trade->id)) {
            // Close out previous stops
            $this->trader->cancelStopsByGroup($group_id);

            // Create Stop Loss
            $this->stopLoss($candle, $trade, 'Added to existing group, updating stop-loss', ['atr' => $trade->recreate->atr]);

            return $trade;
        }

        return true;
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
        return $this->trader->trade($this->currency, $this->asset, $type, $position, $side, $amount, $candle->close, $candle->datetime, $reason, $recreate, $group_id);
    }

    /**
     * @param $n
     * @param ExchangeCandle $candle
     * @return float|int
     */
    protected function calculateUnitSize($n, ExchangeCandle $candle) {
        return ($this->unit_size_account_percent * .01) * $this->account->getAccountSize() / ($n / $candle->close);
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
            $this->log('Cannot buy {$position} position in the market. Already have ' . $units . ' units in play. Max units: #' . $this->max_units_per_market, 'yellow');
            return false;
        } else {
            $this->log('Buying $' . number_format($this->unit_size) . ' worth of ' . $this->asset . ' at $' . number_format($candle->close, 8), 'green');
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

        $this->log("Selling ~$" . number_format($amount * $candle->close, 8) . ' of ' . $this->asset . ' at $' . number_format($candle->close, 8), 'red');

        // Cancel any stops
        $this->cancelStopsByGroup($group_id);

        // Do the sell
        $trade = $this->trade($candle, $amount, AccountTrade::SIDE_SELL, $position, AccountTrade::TYPE_LIMIT, $reason, $recreate, $group_id);

        // Refresh the last order
        if($position == AccountTrade::POSITION_LONG) {
            $this->last_open_long_order = $this->getLastLongOrder();
        } else {
            $this->last_open_short_order = $this->getLastShortOrder();
        }

        return $trade;
    }

    /**
     * Create a Stop-Loss order
     *
     * @param ExchangeCandle $candle
     * @param AccountTrade trade
     * @param string $reason
     * @param array $recreate
     * @return AccountTrade
     */
    protected function stopLoss(ExchangeCandle $candle, AccountTrade $trade, $reason, array $recreate) {
        // Get open orders
        if($trade->position == AccountTrade::POSITION_LONG) {
            $orders = $this->getOpenLongOrders();
            $stop_at = $candle->close - $trade->recreate->atr * $this->atr_multiplier;
        } else {
            $orders = $this->getOpenShortOrders();
            $stop_at = $candle->close + $trade->recreate->atr * $this->atr_multiplier;
        }

        // How much are we selling? (All of it)
        $amount = $orders->reduce(function($amount, $order) {
            return $amount + $order->asset_size;
        }, 0);

        $this->log("Setting a stop loss at $" . number_format($stop_at) . ' of ' . $this->asset, 'cyan');

        return $this->trader->trade($this->currency, $this->asset, AccountTrade::TYPE_STOP, $trade->position, AccountTrade::SIDE_SELL, $amount, $stop_at, $candle->datetime, $reason, $recreate, $trade->group_id);
    }

    /**
     * Cancel stops by group
     *
     * @param $group_id
     */
    protected function cancelStopsByGroup($group_id) {
        return $this->trader->cancelStopsByGroup($group_id);
    }

    /**
     * Get all open long orders
     *
     * @return AccountTrade[]
     */
    protected function getOpenLongOrders() {
        return $this->trader->getOpenOrdersForPosition($this->currency, $this->asset, AccountTrade::POSITION_LONG);
    }

    /**
     * Get all open short orders
     *
     * @return AccountTrade
     */
    protected function getOpenShortOrders() {
        return $this->trader->getOpenOrdersForPosition($this->currency, $this->asset, AccountTrade::POSITION_SHORT);
    }

    /**
     * Get first open long order
     *
     * @return AccountTrade
     */
    protected function getFirstLongOrder() {
        return $this->trader->getFirstOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_LONG);
    }

    /**
     * Get first open short order
     *
     * @return AccountTrade
     */
    protected function getFirstShortOrder() {
        return $this->trader->getFirstOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_SHORT);
    }

    /**
     * Get last open long order
     *
     * @return AccountTrade
     */
    protected function getLastLongOrder() {
        return $this->trader->getLastOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_LONG);
    }

    /**
     * Get first open short order
     *
     * @return AccountTrade
     */
    protected function getLastShortOrder() {
        return $this->trader->getLastOpenOrderForPosition($this->currency, $this->asset, AccountTrade::POSITION_SHORT);
    }

    /**
     * @param $message
     * @param string $color
     */
    protected function log($message, $color = null) {
        if($this->verbose) {
            if($color)
                $message = "<fg={$color}>" . $message . '</>';

            $this->console->writeln("\n" . $message);
        }

        // Log it in the DB too...
    }

    /**
     * @return string
     */
    protected function getName() {
        return 'Turtle';
    }
}