<?php
namespace App\Traders;

use App\Models\AccountTrade;
use App\Models\Exchange;
use App\Models\ExchangeCandle;
use Illuminate\Support\Facades\DB;

class FantasyTrader extends Trader {
    const SLIPPAGE_PERCENT = 0.5;

    /**
     * Perform a fantasy trade
     *
     * @param ExchangeCandle $candle
     * @param $type
     * @param $position
     * @param $side
     * @param $unit_size
     * @param $reason
     * @param $recreate
     * @return AccountTrade
     */
    public function trade(ExchangeCandle $candle, $type, $position, $side, $unit_size, $reason, $recreate) {
        list($slippage, $slippage_percentage) = $this->calculateSplippage($unit_size, $type);
        list($currency_fee, $currency_fee_percentage) = $this->calculateFee($unit_size);
        $currency_amount = $unit_size - $slippage - $currency_fee;
        $asset_size = round($currency_amount / $candle->close, 8);

        return AccountTrade::create([
            'account_id' => $this->account->id,
            'exchange_id' => $this->exchange->id,
            'market' => AccountTrade::MARKET_CRYPTO,
            'type' => $type,
            'position' => $position,
            'side' => $side,
            'status' => AccountTrade::STATUS_FILLED,
            'currency' => $candle->currency,
            'asset' => $candle->asset,
            'currency_per_asset' => $candle->close,
            'asset_size' => $asset_size,
            'currency_slippage_percentage' => $slippage_percentage,
            'currency_splippage' => $slippage,
            'currency_fee_percentage' => $currency_fee_percentage,
            'currency_fee' => $currency_fee,
            'currency_total' => $unit_size,
            'reason' => $reason,
            'recreate' => $recreate,
            'datetime' => $candle->datetime->toDateTimeString()
        ]);
    }

    /**
     * Returns the number of units for a given position
     *
     * @param $currency
     * @param $asset
     * @param $position
     * @return integer
     */
    public function getUnitsForPosition($currency, $asset, $position) {
        if($position == AccountTrade::POSITION_LONG) {
            $count = DB::raw("SUM(IF(account_trades.side = 'buy', 1, 0)) - SUM(IF(account_trades.side = 'sell', 0, 0)) AS count");
        } else {
            $count = DB::raw("SUM(IF(account_trades.side = 'sell', 1, 0)) - SUM(IF(account_trades.side = 'buy', 0, 0)) AS count");
        }

        return AccountTrade::select($count)
            ->where('account_id', $this->account->id)
            ->where('exchange_id', $this->exchange->id)
            ->where('status', AccountTrade::STATUS_FILLED)
            ->where('type', '<>', AccountTrade::TYPE_STOP)
            ->where('currency', $currency)
            ->where('asset', $asset)
            ->where('position', $position)
            ->first()
            ->count;
    }

    /**
     * Returns the number of units for a given market
     *
     * @param $currency
     * @param $asset
     * @return integer
     */
    public function getUnitsForMarket($currency, $asset) {
        return $this->getUnitsForPosition($currency, $asset, AccountTrade::POSITION_LONG)
            + $this->getUnitsForPosition($currency, $asset, AccountTrade::POSITION_SHORT);
    }

    /**
     * Calculate slippage
     *
     * @param $amount
     * @param $type
     * @return array
     */
    protected function calculateSplippage($amount, $type) {
        if($type != AccountTrade::TYPE_MARKET)
            return [0, 0];

        return [$amount * (FantasyTrader::SLIPPAGE_PERCENT * .001), FantasyTrader::SLIPPAGE_PERCENT];
    }

    /**
     * Calculate exchange fee
     *
     * @param $amount
     * @return array
     */
    protected function calculateFee($amount) {
        return [$amount * (Exchange::FEE_PERCENTAGE * .001), Exchange::FEE_PERCENTAGE];
    }
}