<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpenBankingTokensTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('open_banking_tokens', function(Blueprint $table)
        {
            $table->increments('open_banking_token');
            $table->text('token');
            $table->enum('token_for',['bank_details_token','account_information_token','payment_initiation_token'])->default('bank_details_token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('open_banking_tokens');
    }

}
