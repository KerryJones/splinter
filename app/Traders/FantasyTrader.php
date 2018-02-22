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
            'name' => 'Fantasy - ' . Carbon::now()->toDateTimeString(),
            'sandbox' => true
        ]);

        $this->account->ledger->deposit($starting_balance);
    }

    /**
     */
    protected function trade() {
        AccountTrade::create([

        ]);
    }
}