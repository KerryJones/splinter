<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVwAccountTradeUnits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('DROP VIEW IF EXISTS `vw_account_trade_units`;');
        DB::statement("CREATE VIEW `vw_account_trade_units` AS
          SELECT
          account_id,
          exchange_id,
          group_id,
          CONCAT(currency, asset) AS pair,
          position,
          SUM(IF(side = 'buy', 1, 0)) * IF(SUM(IF(side = 'sell', 1, 0)) >= 1, 0, 1) AS units
        FROM account_trades
        WHERE status = 'filled'
        GROUP BY account_id, exchange_id, CONCAT(currency, asset), group_id, position;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS `vw_account_trade_units`;');
    }
}
