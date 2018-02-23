<?php
namespace App\Traders;

use App\Models\Account;
use App\Models\Exchange;
use App\Models\ExchangeCandle;

abstract class Trader {
    /**
     * Holds the exchange in case we need to backfill
     *
     * @var Exchange
     */
    protected $exchange;

    /**
     * Hold the account for this run
     *
     * @var Account
     */
    protected $account;

    public function __construct(Exchange $exchange, Account $account)
    {
        $this->exchange = $exchange;
        $this->account = $account;
    }

    /**
     * @return Exchange
     */
    public function getExchange() {
        return $this->exchange;
    }

    abstract function trade(ExchangeCandle $candle, $type, $position, $side, $unit_size, $reason, $recreate);
    abstract function getUnitsForPosition($currency, $asset, $position);
    abstract function getUnitsForMarket($currency, $asset);
}