<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAccountLedgerItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_ledger_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->enum('type', ['debit', 'credit']);
            $table->decimal('amount', 16, 8);
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
        Schema::dropIfExists('account_ledger_items');
    }
}
