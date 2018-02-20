<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ExchangeCandle extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'exchange_id',
        'currency',
        'asset',
        'interval',
        'datetime',
        'timestamp',
        'open',
        'high',
        'low',
        'close',
        'volume'
    ];

    protected $dates = [
        'datetime'
    ];

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
