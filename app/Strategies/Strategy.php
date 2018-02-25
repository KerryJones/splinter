<?php
namespace App\Strategies;

use App\Libraries\Indicators;
use App\Models\Account;
use App\Models\Exchange;
use App\Models\ExchangeCandle;
use App\Traders\Trader;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Output\Output;

abstract class Strategy {
    /**
     * Holds the account to get relevant data
     *
     * @var Account
     */
    protected $account;

    /**
     * Holds the exchange in case we need to backfill
     *
     * @var Exchange
     */
    protected $exchange;

    /**
     * Holds onto the trader
     *
     * @var Trader
     */
    protected $trader;

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
     * Hold the console to log out to
     *
     * @var OutputStyle
     */
    protected $console;

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

    /****** ABSTACT FUNCTIONS *****/
    abstract protected function executeStrategy(ExchangeCandle $candle);

    /**
     * Create a new Strategy model instance.
     *
     * @param Exchange $exchange
     * @param Trader $trader
     * @param Account $account
     * @param $currency
     * @param $asset
     * @param Carbon $from
     * @param Carbon $to
     * @param int $interval
     * @param OutputStyle|null $console
     */
    public function __construct(Exchange $exchange, Trader $trader, Account $account, $currency, $asset, Carbon $from, Carbon $to, $interval = 4, OutputStyle $console = null)
    {
        $this->exchange = $exchange;
        $this->trader = $trader;
        $this->account = $account;
        $this->currency = $currency;
        $this->asset = $asset;
        $this->from = $from;
        $this->to = $to;
        $this->interval = $interval;
        $this->console = $console;

        $this->candles = $this->exchange->getCandlesByInterval($this->currency, $this->asset, $this->from, $this->to,
            $this->interval
        );

        $this->indicators = new Indicators($this, $this->candles);
    }

    /**
     * Run the main backtesting components
     */
    public function backtest() {
        $bar = $this->console->createProgressBar($this->candles->count());

        $this->candles->each(function(ExchangeCandle $candle, $key) use($bar) {
            $this->candle_key = $key;
            $this->indicators->setCandleIndex($key);

            $this->executeStrategy($candle);
            $bar->advance();
        });
    }

    /**
     * Backfill the candles if necessary
     *
     * @param int $offset
     * @return ExchangeCandle[]
     */
    public function backfill($candles, $offset) {
        $first_candle_datetime = $candles->first()->datetime;
        $from = $first_candle_datetime->copy()->subHours($offset * $this->interval);
        $to = $first_candle_datetime->copy()->subHours($this->interval);

        $new_candles = $this->exchange->getCandlesByInterval($this->currency, $this->asset, $from, $to, $this->interval);

        $candles->each(function($candle) use($new_candles) {
            $new_candles->push($candle);
        });

        return $new_candles;
    }

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