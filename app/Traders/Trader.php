<?php
namespace App\Traders;

use App\Models\Exchange;

abstract class Trader {
    /**
     * Holds the exchange in case we need to backfill
     *
     * @var Exchange
     */
    protected $exchange;

    public function __construct(Exchange $exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * @return Exchange
     */
    public function getExchange() {
        return $this->exchange;
    }

    protected function trade() {

    }
}