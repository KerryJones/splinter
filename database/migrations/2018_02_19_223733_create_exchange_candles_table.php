<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateExchangeCandlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_candles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('exchange_id');
            $table->string('currency', 10); // USD
            $table->string('asset', 10); // BTC
            $table->dateTime('datetime');
            $table->bigInteger('timestamp');
            $table->string('interval', 10); // 1h, 2h, 4h, 8h, 12h, 1d, 1w, 1m
            $table->decimal('open', 16, 8);
            $table->decimal('high', 16, 8);
            $table->decimal('low', 16, 8);
            $table->decimal('close', 16, 8);
            $table->decimal('volume', 16, 8);
            $table->timestamps();
            $table->softDeletes();

            $table->index('exchange_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('exchange_candles');
    }
}
