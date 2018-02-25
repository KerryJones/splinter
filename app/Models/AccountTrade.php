<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AccountTrade extends Model
{
    const MARKET_CRYPTO = 'crypto';
    const MARKET_STOCK = 'stock';
    const MARKET_FOREX = 'forex';
    const MARKET_COMMODITIES = 'commodities';

    const TYPE_LIMIT = 'limit';
    const TYPE_MARKET = 'market';
    const TYPE_STOP = 'stop';

    const POSITION_LONG = 'long';
    const POSITION_SHORT = 'short';

    const SIDE_BUY = 'buy';
    const SIDE_SELL = 'sell';

    const STATUS_OPEN = 'open';
    const STATUS_FILLED = 'filled';
    const STATUS_CANCELED = 'canceled';

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
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
        'currency_slippage',
        'currency_fee_percentage',
        'currency_fee',
        'currency_total',
        'reason',
        'recreate',
        'group_id',
        'datetime',
        'date_filled'
    ];

    protected $dates = [
        'datetime',
        'date_filled'
    ];

    /**
     * An account trade belongs to an account
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(\App\Models\Account::class);
    }

    /**
     * An account trade belongs to an exchange
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exchange()
    {
        return $this->belongsTo(\App\Models\Exchange::class);
    }

    /**
     * Turn it back into an array
     */
    public function getRecreateAttribute($value) {
        return $value ? json_decode($value) : '';
    }

    /**
     * Joins any query from account trades
     *
     * @param $query
     * @return mixed
     */
    public function scopeJoinAccountTradeGroups($query) {
        return $query->leftJoin('vw_account_trade_groups', function($join) {
            $join->on('vw_account_trade_groups.account_id', '=', 'account_trades.account_id')
                ->on('vw_account_trade_groups.exchange_id', '=', 'account_trades.exchange_id')
                ->on('vw_account_trade_groups.position', '=', 'account_trades.position')
                ->on('vw_account_trade_groups.pair', '=', DB::raw('CONCAT(account_trades.currency, account_trades.asset)'))
                ->on(DB::raw('COALESCE(vw_account_trade_groups.group_id, 0)'), '=', DB::raw('COALESCE(account_trades.group_id, 0)'));
        });
    }
}
