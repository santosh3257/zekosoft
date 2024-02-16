<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVendorsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vendors', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('added_by')->unsigned()->nullable();
            $table->foreign('added_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('name',250)->nullable(false);
            $table->string('ssn', 250)->nullable(false);
            $table->string('vat_number')->nullable(false);
            $table->string('email',191)->nullable(false);
            $table->string('phone_number',20)->nullable();
            $table->string('bankgiro',191)->nullable(false);
            $table->string('plusgiro',191)->nullable();
            $table->string('iban',191)->nullable();
            $table->string('bic',191)->nullable();
            $table->string('bank',191)->nullable();
            $table->string('clearing_num',191)->nullable();
            $table->string('account_num',191)->nullable();
            $table->enum('bank_fee', ['sender', 'receiver','both'])->default('sender');
            $table->text('billing_address')->nullable(false);
            $table->string('country',50)->nullable(false);
            $table->string('state',100)->nullable(false);
            $table->string('city',100)->nullable(false);
            $table->string('postal_code',50)->nullable(false);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('note')->nullable();
            $table->integer('locale')->unsigned()->nullable();
            $table->foreign('locale')
                ->references('id')
                ->on('language_settings')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->integer('currency_id')->unsigned()->nullable();
            $table->foreign('currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->integer('tax_id')->unsigned()->nullable();
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
        Schema::drop('vendors');
    }

}
