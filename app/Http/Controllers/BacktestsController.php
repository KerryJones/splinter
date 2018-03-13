<?php

namespace App\Http\Controllers;

use App\Models\AccountTrade;
use App\Models\StrategyBacktest;
use Carbon\Carbon;

class BacktestsController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getCandlesCsv(StrategyBacktest $backtest) {
        $candles = $backtest->getCandles()->map(function($candle) {
            return [$candle->datetime->toDateTimeString(), $candle->open, $candle->high, $candle->close, $candle->low, $candle->volume];
        });

        return response()->stream(getStream($candles->prepend(['DateTime', 'Open', 'High', 'Close', 'Low', 'Volume'])->toArray()), 200);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getTradesCsv(StrategyBacktest $backtest) {
        $trades = $backtest->getTrades()->map(function($trade) {
            return [$trade->datetime->toDateTimeString(), $trade->getGraphAbbreviation(), $trade->type . ' for ' . $trade->asset_size, $trade->reason];
        });

        return response()->stream(getStream($trades->prepend(['DateTime', 'Type', 'Title', 'Description'])->toArray()), 200);
    }

    public function export(StrategyBacktest $backtest) {
        $trades = $backtest->getTrades()->keyBy('date_filled');

        $candles = $backtest->getCandles()->map(function($candle) use($trades) {
            $base = [$candle->datetime->toDateTimeString(), $candle->open, $candle->high, $candle->close, $candle->low];

            if(isset($trades[$candle->datetime->toDateTimeString()])) {
                $trade = $trades[$candle->datetime->toDateTimeString()];
                $base[] = $trade->side . ' ' . $trade->position . ' - ' . $trade->type;

                $recreate_values = [];
                foreach($trade->recreate as $key => $value) {
                    $recreate_values[] = "{$key} = {$value}";
                }

                $base[] = implode(', ', $recreate_values);

            } else {
                $base[] = '';
                $base[] = '';
            }

            return $base;
        })->toArray();

        array_unshift($candles, [
            'Date', 'Open', 'High', 'Close', 'Low', 'Type', 'Recreate'
        ]);

        return response()->stream(getStream($candles), 200, csv_headers('trades-9-7'));
    }
}
