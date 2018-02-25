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
     * @param string $currency
     * @param string $asset
     * @param string $type
     * @param string $position
     * @param string $side
     * @param float $amount
     * @param string $reason
     * @param array $recreate
     * @param string|null group_id
     * @return AccountTrade
     */
    public function trade(ExchangeCandle $candle, $currency, $asset, $type, $position, $side, $amount, $reason, array $recreate, $group_id = null) {
        // Dealing with currency
        list($slippage, $slippage_percentage) = $this->calculateSplippage($amount, $type);
        list($fee, $fee_percentage) = $this->calculateFee($amount);

        if($side == AccountTrade::SIDE_BUY) {
            $currency_amount = $amount - $slippage - $fee;
            $asset_size = round($currency_amount / $candle->close, 8);
            $currency_fee = $fee;
        } else {
            $asset_size = $amount - $slippage - $fee;
            $currency_amount = round($asset_size * $candle->close, 8);
            $currency_fee = round($fee * $candle->close, 8);
            $slippage = round($slippage * $candle->close, 8);
        }

        return AccountTrade::create([
            'account_id' => $this->account->id,
            'exchange_id' => $this->exchange->id,
            'market' => AccountTrade::MARKET_CRYPTO,
            'type' => $type,
            'position' => $position,
            'side' => $side,
            'status' => AccountTrade::STATUS_FILLED,
            'currency' => $currency,
            'asset' => $asset,
            'currency_per_asset' => $candle->close,
            'asset_size' => $asset_size,
            'currency_slippage_percentage' => $slippage_percentage,
            'currency_slippage' => $slippage,
            'currency_fee_percentage' => $fee_percentage,
            'currency_fee' => $currency_fee,
            'currency_total' => $currency_amount,
            'reason' => $reason,
            'recreate' => json_encode($recreate),
            'group_id' => $group_id,
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
     * Get all open orders for a position
     *
     * @param $currency
     * @param $asset
     * @param $position
     * @return AccountTrade[]
     */
    public function getOpenOrdersForPosition($currency, $asset, $position) {
        return AccountTrade::select('account_trades.*')
            ->joinAccountTradeGroups()
            ->where('account_trades.account_id', $this->account->id)
            ->where('account_trades.exchange_id', $this->exchange->id)
            ->where('account_trades.status', AccountTrade::STATUS_FILLED)
            ->where('account_trades.currency', $currency)
            ->where('account_trades.asset', $asset)
            ->where('account_trades.position', $position)
            // Make sure there is 2% or more in the market to be considered
            ->where('vw_account_trade_groups.percent_in_market', '>', 2)
            ->orderBy('account_trades.datetime', 'ASC')
            ->get();
    }

    /**
     * Returns the the first trade for a position
     *
     * @param $currency
     * @param $asset
     * @param $position
     * @return AccountTrade
     */
    public function getFirstOpenOrderForPosition($currency, $asset, $position) {
        return AccountTrade::select('account_trades.*')
            ->joinAccountTradeGroups()
            ->where('account_trades.account_id', $this->account->id)
            ->where('account_trades.exchange_id', $this->exchange->id)
            ->where('account_trades.status', AccountTrade::STATUS_FILLED)
            ->where('account_trades.currency', $currency)
            ->where('account_trades.asset', $asset)
            ->where('account_trades.position', $position)
            // Make sure there is 2% or more in the market to be considered
            ->where('vw_account_trade_groups.percent_in_market', '>', 2)
            ->orderBy('account_trades.datetime', 'ASC')
            ->first();
    }

    /**
     * Returns the the first trade for a position
     *
     * @param $currency
     * @param $asset
     * @param $position
     * @return AccountTrade
     */
    public function getLastOpenOrderForPosition($currency, $asset, $position) {
        return AccountTrade::select('account_trades.*')
            ->joinAccountTradeGroups()
            ->where('account_trades.account_id', $this->account->id)
            ->where('account_trades.exchange_id', $this->exchange->id)
            ->where('account_trades.status', AccountTrade::STATUS_FILLED)
            ->where('account_trades.currency', $currency)
            ->where('account_trades.asset', $asset)
            ->where('account_trades.position', $position)
            // Make sure there is 2% or more in the market to be considered
            ->where('vw_account_trade_groups.percent_in_market', '>', 2)
            ->orderBy('account_trades.datetime', 'DESC')
            ->first();
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