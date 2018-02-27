<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStrategyBacktestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('strategy_backtests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('exchange_id');
            $table->string('strategy', 100);
            $table->dateTime('from');
            $table->dateTime('to');
            $table->string('currency', 20);
            $table->string('asset', 20);
            $table->string('interval', 20);
            $table->text('records');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id', 'exchange_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('strategy_backtests');
    }
}
