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
     * Gets the summary of an account
     *
     * @return mixed
     */
    public function getSummary($exchange_id) {
        $trade_summary = AccountTrade::select([
                'account_id',
                DB::raw('COUNT(DISTINCT id) AS total_trades'),
                DB::raw('COUNT(DISTINCT group_id) AS groups'),
                DB::raw("SUM(IF(position = 'long', 1, 0)) AS longs"),
                DB::raw("SUM(IF(position = 'short', 1, 0)) AS shorts"),
                DB::raw("SUM(IF(type = 'stop', 1, 0)) AS stop_losses"),
            ])
            ->where('account_id', $this->id)
            ->where('exchange_id', $exchange_id)
            ->groupBy('account_id', 'exchange_id')
            ->first()
            ->toArray();

        $group_summary = (array) DB::table('vw_account_trade_group_summaries')
            ->select([
                'account_id',
                DB::raw('SUM(profit) AS profit'),
                DB::raw('CONCAT(ROUND(SUM(profit) / (SELECT SUM(amount) FROM account_ledger_items WHERE account_ledger_items.account_id = vw_account_trade_group_summaries.account_id) * 100, 2), \'%\') AS profit_percent'),
                DB::raw('SUM(IF(profit > 0, 1, 0)) AS winning_groups'),
                DB::raw('SUM(IF(profit <= 0, 1, 0)) AS losing_groups'),
                DB::raw('AVG(profit) AS avg_profit'),
                DB::raw('AVG(IF(profit > 0, profit, 0)) AS avg_profit_gain'),
                DB::raw('AVG(IF(profit <= 0, profit, 0)) AS avg_profit_loss'),
                DB::raw('AVG(IF(position = \'long\', profit, 0)) AS avg_profit_long'),
                DB::raw('AVG(IF(position = \'short\', profit, 0)) AS avg_profit_short'),
                DB::raw('AVG(DATEDIFF(datetime_exit, datetime_entry)) AS avg_length_held_in_days')
            ])
            ->where('account_id', $this->id)
            ->where('exchange_id', $exchange_id)
            ->groupBy('account_id', 'exchange_id')
            ->first();

        return collect(array_merge($trade_summary, $group_summary));
    }
}
