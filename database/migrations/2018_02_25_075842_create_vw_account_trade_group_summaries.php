<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVwAccountTradeGroupSummaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('DROP VIEW IF EXISTS `vw_account_trade_group_summaries`;');
        DB::statement("CREATE VIEW `vw_account_trade_group_summaries` AS
          SELECT
          account_trades.group_id,
          account_trades.account_id,
          account_trades.exchange_id,
          account_trades.position,
          MIN(account_trades.datetime) AS datetime_entry,
          MAX(account_trades.datetime) AS datetime_exit,
          SUM(IF(side = 'buy', 1, 0)) AS units,
          IF(account_trades.position = 'long',
             SUM(IF(side = 'sell', currency_total, 0)) - SUM(IF(side = 'buy', currency_total, 0)),
             SUM(IF(side = 'buy', currency_total, 0)) - SUM(IF(side = 'sell', currency_total, 0))
          ) AS profit
        FROM account_trades
          JOIN vw_account_trade_groups ON vw_account_trade_groups.group_id = account_trades.group_id
        WHERE status = 'filled' AND vw_account_trade_groups.percent_in_market < 2
        GROUP BY account_trades.account_id, account_trades.exchange_id, account_trades.group_id, account_trades.position;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS `vw_account_trade_group_summaries`;');
    }
}
