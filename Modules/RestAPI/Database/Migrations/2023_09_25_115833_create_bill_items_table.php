<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillItemsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bill_items', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('bill_id')->unsigned()->nullable();
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('article_id');
            $table->mediumText('note')->nullable();
            $table->tinyInteger('quantity');
            $table->string('unit');
            $table->double('rate');
            $table->double('amount');
            $table->double('tax_amount');
            $table->integer('tax_id');
            $table->integer('account_code_id');
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
        Schema::drop('bill_items');
    }

}
