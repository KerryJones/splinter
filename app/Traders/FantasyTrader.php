<?php
namespace App\Traders;

use App\Models\Account;
use App\Models\AccountTrade;
use App\Models\Exchange;
use Carbon\Carbon;

class FantasyTrader extends Trader {
    /**
     * Hold the account for this run
     *
     * @var Account
     */
    protected $account;

    /**
     * Fantasy constructor.
     *
     * @param $starting_balance
     */
    public function __construct(Exchange $exchange, $starting_balance)
    {
        parent::__construct($exchange);

        $this->account = Account::create([
            'name' => 'Fantasy Trader - ' . Carbon::now()->toDateTimeString(),
            'sandbox' => true
        ]);

        $this->account->ledger->deposit($starting_balance);
    }

    /**
     */
    public function trade() {
        AccountTrade::create([
            'account_id',
            'exchange_id',
            'market',
            'type',
            'position',
            'side',
            'status',
            'currency',
            'asset',
            'currency_per_asset',
            'asset_size',
            'currency_slippage_percentage',
            'currency_splippage',
            'currency_fee_percentage',
            'currency_fee',
            'currency_total',
            'reason',
            'recreate',
            'datetime' => Carbon::now()->toDateTimeString()
        ]);
    }
}