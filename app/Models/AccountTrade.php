<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountTrade extends Model
{
    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';

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
        'amount',
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
