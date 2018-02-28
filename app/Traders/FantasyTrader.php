<?php
namespace App\Traders;

use App\Models\AccountTrade;
use App\Models\Exchange;
use App\Models\ExchangeCandle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\Diff\Output\AbstractChunkOutputBuilder;

class FantasyTrader extends Trader {
    const SLIPPAGE_PERCENT = 0.5;

    /**
     * Determine if we want to cache results -- much faster, possibly less reliable
     */
    const CACHE = true;

    protected $last_open_long_position = null;
    protected $last_open_short_position = null;

    /**
     * Perform a fantasy trade
     *
     * @param string $currency
     * @param string $asset
     * @param string $type
     * @param string $position
     * @param string $side
     * @param float $amount
     * @param float $currency_of_asset
     * @param Carbon $datetime
     * @param string $reason
     * @param array $recreate
     * @param string|null group_id
     * @return AccountTrade
     */
    public function trade($currency, $asset, $type, $position, $side, $amount, $currency_of_asset, Carbon $datetime, $reason, array $recreate, $group_id = null) {
        // Dealing with currency
        list($slippage, $slippage_percentage) = $this->calculateSplippage($amount, $type);
        list($fee, $fee_percentage) = $this->calculateFee($amount);

        if ($side == AccountTrade::SIDE_BUY) {
            $currency_amount = $amount - $slippage - $fee;
            $asset_size = round($currency_amount / $currency_of_asset, 8);
            $currency_fee = $fee;
        } else {
            $asset_size = $amount - $slippage - $fee;
            $currency_amount = round($asset_size * $currency_of_asset, 8);
            $currency_fee = round($fee * $currency_of_asset, 8);
            $slippage = round($slippage * $currency_of_asset, 8);
        }

        // Stop Order
        if($type == AccountTrade::TYPE_STOP) {
            $status = AccountTrade::STATUS_OPEN;
            $date_filled = null;
        } else {
            $status = AccountTrade::STATUS_FILLED;
            $date_filled = $datetime->toDateTimeString();
        }

        $trade = AccountTrade::create([
            'account_id' => $this->account->id,
            'exchange_id' => $this->exchange->id,
            'market' => AccountTrade::MARKET_CRYPTO,
            'type' => $type,
            'position' => $position,
            'side' => $side,
            'status' => $status,
            'currency' => $currency,
            'asset' => $asset,
            'currency_per_asset' => $currency_of_asset,
            'asset_size' => $asset_size,
            'currency_slippage_percentage' => $slippage_percentage,
            'currency_slippage' => $slippage,
            'currency_fee_percentage' => $fee_percentage,
            'currency_fee' => $currency_fee,
            'currency_total' => $currency_amount,
            'reason' => $reason,
            'recreate' => json_encode($recreate),
            'group_id' => $group_id,
            'datetime' => $datetime->toDateTimeString(),
            'date_filled' => $date_filled
        ]);

        $this->updateLastOpenPositions($trade);

        return $trade;
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
        $row = DB::table('vw_account_trade_units')
            ->select(DB::raw('SUM(COALESCE(units, 0)) AS units'))
            ->where('account_id', $this->account->id)
            ->where('exchange_id', $this->exchange->id)
            ->where('pair', $currency . $asset)
            ->where('position', $position)
            ->first();

        return $row ? $row->units : 0;
    }

    /**
     * Returns the number of units for a given market
     *
     * @param $currency
     * @param $asset
     * @return integer
     */
    public function getUnitsForMarket($currency, $asset) {
        $row = DB::table('vw_account_trade_units')
            ->select(DB::raw('SUM(COALESCE(units, 0)) AS units'))
            ->where('account_id', $this->account->id)
            ->where('exchange_id', $this->exchange->id)
            ->where('pair', $currency . $asset)
            ->first();

        return $row ? $row->units : 0;
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
            ->where('account_trades.side', AccountTrade::SIDE_BUY)
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
        if(self::CACHE && $position == AccountTrade::POSITION_LONG && !is_null($this->last_open_long_position)) {
            return $this->last_open_long_position;
        } elseif(self::CACHE && $position == AccountTrade::POSITION_SHORT && !is_null($this->last_open_short_position)) {
            return $this->last_open_short_position;
        }

        $trade = AccountTrade::select('account_trades.*')
            ->joinAccountTradeGroups()
            ->where('account_trades.account_id', $this->account->id)
            ->where('account_trades.exchange_id', $this->exchange->id)
            ->where('account_trades.status', AccountTrade::STATUS_FILLED)
            ->where('account_trades.side', AccountTrade::SIDE_BUY)
            ->where('account_trades.currency', $currency)
            ->where('account_trades.asset', $asset)
            ->where('account_trades.position', $position)
            // Make sure there is 2% or more in the market to be considered
            ->where('vw_account_trade_groups.percent_in_market', '>', 2)
            ->orderBy('account_trades.datetime', 'DESC')
            ->first();

        if(!self::CACHE)
            return $trade;

        if(!is_null($trade)) {
            $this->updateLastOpenPositions($trade);
        } else {
            if($position == AccountTrade::POSITION_LONG) {
                $this->last_open_long_position = false;
            } elseif($position == AccountTrade::POSITION_SHORT) {
                $this->last_open_short_position = false;
            }
        }

        return $trade;
    }

    /**
     * Get any open stops
     *
     * @param $currency
     * @param $asset
     * @return array
     */
    public function getOpenStops($currency, $asset)
    {
        $stop_trades = AccountTrade::where('account_id', $this->account->id)
            ->where('exchange_id', $this->exchange->id)
            ->where('currency', $currency)
            ->where('asset', $asset)
            ->where('type', AccountTrade::TYPE_STOP)
            ->where('status', AccountTrade::STATUS_OPEN);

        $long = $short = null;

        $stop_trades->each(function(AccountTrade $trade) use(&$long, &$short) {
            if($trade->position == AccountTrade::POSITION_LONG) {
                $long = $trade;
            } else {
                $short = $trade;
            }
        });

        return compact('long', 'short');
    }

    /**
     * Fill an order
     *
     * @param AccountTrade $trade
     * @param ExchangeCandle $candle
     */
    public function fillOrder(AccountTrade $trade, ExchangeCandle $candle) {
        $trade->update([
            'status' => AccountTrade::STATUS_FILLED,
            'date_filled' => $candle->datetime->toDateTimeString()
        ]);

        if($trade->position == AccountTrade::POSITION_LONG) {
            $this->last_open_long_position = false;
        } else {
            $this->last_open_short_position = false;
        }
    }

    /**
     * Cancel any open stops
     *
     * @param $group_id
     * @return bool
     */
    public function cancelStopsByGroup($group_id)
    {
        AccountTrade::where('account_id', $this->account->id)
            ->where('exchange_id', $this->exchange->id)
            ->where('group_id', $group_id)
            ->where('type', AccountTrade::TYPE_STOP)
            ->update(['status' => AccountTrade::STATUS_CANCELED]);

        return true;
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

    /**
     * Updates the cache
     *
     * @param AccountTrade $trade
     */
    protected function updateLastOpenPositions(AccountTrade $trade) {
        if($trade->status != AccountTrade::STATUS_FILLED)
            return;

        if ($trade->side == AccountTrade::SIDE_BUY) {
            if($trade->position == AccountTrade::POSITION_LONG) {
                $this->last_open_long_position = $trade;
            } else {
                $this->last_open_short_position = $trade;
            }
        } else {
            if($trade->position == AccountTrade::POSITION_LONG) {
                $this->last_open_long_position = null;
            } else {
                $this->last_open_short_position = null;
            }
        }
    }
}