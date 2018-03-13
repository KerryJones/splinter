<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Account extends Model
{
    use SoftDeletes;

    /**
     * Hold the account ledger
     *
     * @var AccountLedger
     */
    protected $account_ledger;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'sandbox'
    ];

    /**
     * An account belongs to many exchanges.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function exchanges()
    {
        return $this->belongsToMany(\App\Models\Exchange::class, 'account_exchange', 'account_id', 'exchange_id');
    }

    /**
     * @return AccountLedger
     */
    public function getLedgerAttribute() {
        if(is_null($this->account_ledger))
            $this->account_ledger = new AccountLedger($this);

        return $this->account_ledger;
    }

    /**
     * Alias for ledger balance
     *
     * @return float
     */
    public function getAccountSize() {
        return $this->ledger->getBalance();
    }

    /**
     * Get trades belonging to this account
     *
     * @param null $status
     * @return mixed
     */
    public function getTrades($status = null) {
        $selection = AccountTrade::where('account_id', $this->id);

        if($status) {
            $selection->where('status', $status);

            if($status == AccountTrade::STATUS_FILLED)
                $selection->orderBy('date_filled');
        }

        return $selection->get();
    }

    /**
     * Get trades belonging to this account
     *
     * @return mixed
     */
    public function getTradeBalance() {
        $balance = AccountTrade::select(DB::raw("SUM(IF(side = 'sell', currency_total, -1 * currency_total)) AS balance"))
            ->where('account_id', $this->id)
            ->where('status', AccountTrade::STATUS_FILLED)
            ->groupBy('account_id')
            ->first();

        return $balance ? $balance->balance : 0;
    }
}
