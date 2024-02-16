<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExpensesFieldsToExpenses extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expenses', function(Blueprint $table)
        {
            $table->double('total_amount')->nullable();
            $table->double('sub_total')->nullable();
            $table->double('vat_amount')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->integer('account_code_id')->nullable();
            $table->integer('language_id')->nullable();
            $table->string('assign_to',250)->nullable();
            $table->integer('assign_to_id')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expenses', function(Blueprint $table)
        {
            $table->dropColumn(['total_amount', 'sub_total','vat_amount','mode_of_payment','account_code_id','language_id','assign_to','assign_to_id']);
        });
    }

}
