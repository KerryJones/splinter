<?php

namespace App\Models;

use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\DB;
use JMS\Serializer\Tests\Fixtures\Discriminator\Car;
use Stripe;

class AccountLedger
{

    /**
     * Hold the main account
     *
     * @var Account
     */
    protected $account;

    /**
     * Hold the account balance
     *
     * @var float
     */
    protected $balance = 0;

    public function __construct(Account $account) {
        $this->account = $account;
    }

    /**
     * Get the current account balance
     *
     * @return float
     */
    public function getBalance() {
        if(isset($this->balance))
            return $this->balance;

        $this->reloadBalance();

        return (float) $this->balance;
    }

    /**
     * Gets the balance from a calculation from the database
     */
    protected function reloadBalance() {
        $this->balance = AccountLedgerItem::select(DB::raw('SUM(amount) AS balance'))
            ->where('account_id', $this->account->id)
            ->first()->balance;
    }

    /**
     * Deposit something into this account's ledger
     *
     * @param $amount
     * @return AccountLedgerItem
     */
    public function deposit($amount)
    {
        $amount = (float) preg_replace("/[^0-9.]/", '', $amount);

        $ledger_item = AccountLedgerItem::create([
            'account_id' => $this->account->id,
            'amount' => $amount,
            'type' => AccountLedgerItem::TYPE_DEBIT,
            'datetime' => Carbon::now()->toDateTimeString(),
        ]);

        $this->reloadBalance();

        return $ledger_item;
    }

    /**
     * Withdraw from the account
     *
     * @param $amount
     * @return AccountLedgerItem
     */
    public function withdraw($amount) {
        $ledger_item = TenantLedgerItem::create([
            'tenant_id' => $this->account->id,
            'amount' => $amount * -1,
            'type' => AccountLedgerItem::TYPE_CREDIT,
            'datetime' => Carbon::now()->toDateTimeString(),
        ]);

        $this->reloadBalance();

        return $ledger_item;
    }
}
