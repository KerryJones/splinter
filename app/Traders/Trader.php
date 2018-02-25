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

    abstract function trade(ExchangeCandle $candle, $currency, $asset, $type, $position, $side, $amount, $reason, array $recreate, $group_id = null);
    abstract function getUnitsForPosition($currency, $asset, $position);
    abstract function getUnitsForMarket($currency, $asset);
    abstract function getOpenOrdersForPosition($currency, $asset, $position);
    abstract function getFirstOpenOrderForPosition($currency, $asset, $position);
    abstract function getLastOpenOrderForPosition($currency, $asset, $position);
}