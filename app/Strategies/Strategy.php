<?php
namespace App\Strategies;

use App\Libraries\Indicators;
use App\Models\Exchange;
use App\Models\ExchangeCandle;
use Carbon\Carbon;

abstract class Strategy {
    /**
     * Holds the exchange in case we need to backfill
     *
     * @var Exchange
     */
    protected $exchange;

    /**
     * The primary symbol (currency) you are using to buy or sell.
     *
     * Example: USD
     *
     * @var string
     */
    protected $currency;

    /**
     * The primary symbol being treated as an asset.
     *
     * Example: BTC
     *
     * @var string
     */
    protected $asset;


    /**
     * The time interval for the candles expressed in hours
     *
     * @var integer
     */
    protected $interval;

    /**
     * The date to start from
     *
     * @var Carbon
     */
    protected $from;

    /**
     * The date to end at
     *
     * @var Carbon
     */
    protected $to;

    /**
     * Holds all the candles that have been imported
     *
     * @var array|ExchangeCandle[]
     */
    protected $candles = [];

    /**
     * Holds a library of functions for indicators
     *
     * @var Indicators
     */
    protected $indicators;

    /**
     * Holds the current position of the candle
     *
     * @var integer
     */
    private $candle_index = 0;

    /**
     * Create a new Strategy model instance.
     *
     * @param Exchange $exchange
     * @param $currency
     * @param $asset
     * @param Carbon $from
     * @param Carbon $to
     * @param int $interval
     */
    public function __construct(Exchange $exchange, $currency, $asset, Carbon $from, Carbon $to, $interval = 4)
    {
        $this->exchange = $exchange;
        $this->currency = $currency;
        $this->asset = $asset;
        $this->from = $from;
        $this->to = $to;
        $this->interval = $interval;

        $this->candles = $this->exchange->getCandlesByInterval($this->currency, $this->asset, $this->from, $this->to,
            $this->interval
        );

        $this->indicators = new Indicators($this, $this->candles);
    }

    /**
     * Run the main backtesting components
     */
    public function backtest() {
        $this->candles->each(function(ExchangeCandle $candle, $key) {
            $this->candle_key = $key;
            $this->indicators->setCandleIndex($key);

            $this->enter($candle);
        });
    }

    /**
     * Backfill the candles if necessary
     *
     * @param int $offset
     * @return ExchangeCandle[]
     */
    public function backfill($offset) {
        $from = $this->from->copy()->subHours($offset * $this->interval);
        $to = $this->from;

        return $this->exchange->getCandlesByInterval($this->currency, $this->asset, $from, $to, $this->interval);
    }

    abstract protected function enter(ExchangeCandle $candle);
    abstract protected function exit(ExchangeCandle $candle);

    /***** ALIASES *****/

    /**
     * Gets the highest closing value of the last n candles
     *
     * @param $length
     * @return float
     */
    public function highest($length) {
        return $this->indicators->highest($length);
    }

    /**
     * Get's the highest closing value of the last n candles
     *
     * @param $length
     * @return float
     */
    public function lowest($length) {
        return $this->indicators->lowest($length);
    }
}