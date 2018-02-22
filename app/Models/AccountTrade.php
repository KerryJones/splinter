<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'id',
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
        'datetime'
    ];

    protected $dates = [
        'datetime'
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
}
