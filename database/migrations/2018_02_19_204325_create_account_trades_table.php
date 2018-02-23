<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountTradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_trades', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('exchange_id');
            $table->string('market')->enum(['crypto', 'stock', 'forex', 'commodities']);
            $table->string('type')->enum(['limit', 'market', 'stop']);
            $table->string('position')->enum(['long', 'short']);
            $table->string('side')->enum(['buy', 'sell']);
            $table->string('status')->enum(['open', 'filled', 'canceled']);
            $table->string('currency', 10); // USD
            $table->string('asset', 10); // BTC
            $table->decimal('currency_per_asset', 16, 8); // $14,500.78
            $table->decimal('asset_size', 16, 8); // 1.00005000
            $table->decimal('currency_slippage_percentage', 16, 8); // .05%
            $table->decimal('currency_slippage', 16, 8); // $8.00
            $table->decimal('currency_fee_percentage', 6, 6); // .25%
            $table->decimal('currency_fee', 16, 8); // $5.00
            $table->decimal('currency_total', 16, 8); // currency_per_asset * asset_size - slippage - fee
            $table->string('reason');
            $table->text('recreate');
            $table->dateTime('datetime');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_trades');
    }
}
