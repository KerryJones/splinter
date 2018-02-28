<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class StrategyBacktest extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'exchange_id',
        'strategy',
        'currency',
        'asset',
        'from',
        'to',
        'interval',
        'records',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'drawdown_percentage',
        'profit_percentage',
        'buy_and_hold_percentage'
    ];

    protected $dates = [
        'from',
        'to'
    ];

    /**
     * An account backtest belongs to an account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class);
    }

    /**
     * Gets the summary of an account
     *
     * @return mixed
     */
    public function getSummary() {
        $trade_summary = AccountTrade::select([
                'account_id',
                DB::raw('COUNT(DISTINCT id) AS total_trades'),
                DB::raw('COUNT(DISTINCT group_id) AS groups'),
                DB::raw("SUM(IF(position = 'long', 1, 0)) AS longs"),
                DB::raw("SUM(IF(position = 'short', 1, 0)) AS shorts"),
                DB::raw("SUM(IF(type = 'stop', 1, 0)) AS stop_losses"),
            ])
            ->where('account_id', $this->account_id)
            ->where('exchange_id', $this->exchange_id)
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
            ->where('account_id', $this->account_id)
            ->where('exchange_id', $this->exchange_id)
            ->groupBy('account_id', 'exchange_id')
            ->first();

        return collect(array_merge($trade_summary, $group_summary));
    }
}
