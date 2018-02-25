<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVwAccountTradeGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('DROP VIEW IF EXISTS `vw_account_trade_groups`;');
        DB::statement('CREATE VIEW `vw_account_trade_groups` AS
          SELECT
          group_id,
          account_id,
          exchange_id,
          CONCAT(currency, asset)                                                                         AS pair,
          SUM(IF(side = \'buy\', asset_size, -1 * asset_size))                                              AS asset_in_market,
          SUM(IF(side = \'buy\', asset_size, -1 * asset_size)) / SUM(IF(side = \'buy\', asset_size, 0)) * 100 AS percent_in_market,
          position
        FROM account_trades
        WHERE status = \'filled\' AND deleted_at IS NULL
        GROUP BY account_id, exchange_id, CONCAT(currency, asset), group_id, position;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS `vw_account_trade_groups`;');
    }
}
