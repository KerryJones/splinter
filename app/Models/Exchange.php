<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Exchange extends Model
{
    use SoftDeletes;

    const FEE_PERCENTAGE = .25;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name'
    ];

    /**
     * @param $currency
     * @param $asset
     * @param Carbon $from
     * @param Carbon $to
     * @param int $interval [hours]
     * @return mixed
     */
    public function getCandlesByInterval($currency, $asset, Carbon $from, Carbon $to, $interval = 4) {
        $interval *= 3600;

        return ExchangeCandle::select([
                DB::raw('MIN(datetime) AS datetime'),
                DB::raw('UNIX_TIMESTAMP(datetime) DIV ' . $interval . ' AS timestamp'),
                DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(CAST(open AS CHAR) ORDER BY datetime), ',', 1 ) as open"),
                DB::raw('MAX(high) AS high'),
                DB::raw('MIN(low) AS low'),
                DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(CAST(close AS CHAR) ORDER BY datetime DESC), ',', 1 ) as close"),
                DB::raw('SUM(volume) AS volume'),
            ])
            ->where('exchange_id', $this->id)
            ->where('currency', $currency)
            ->where('asset', $asset)
            ->whereBetween('datetime', [$from, $to])
            ->groupBy(DB::raw('UNIX_TIMESTAMP(datetime) DIV ' . $interval))
            ->orderBy('datetime')
            ->get();
    }
}
