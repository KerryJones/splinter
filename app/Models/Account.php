<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
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
}
