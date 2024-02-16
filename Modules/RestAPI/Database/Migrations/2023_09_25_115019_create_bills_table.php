<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBillsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bills', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('added_by');
            $table->integer('vendor_id')->unsigned()->nullable(false);
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade')->onUpdate('cascade');
            $table->string('bill_no',250)->nullable();
            $table->string('ocr_no',250)->nullable();
            $table->date('date_of_issue')->nullable();
            $table->date('due_date')->nullable();
            $table->double('total_amount');
            $table->double('sub_total');
            $table->double('vat_tax_amount');
            $table->string('bill_attachment',250)->nullable();
            $table->enum('status', ['paid', 'unpaid','pending payment','overdue','draft'])->default('draft');
            $table->integer('archive')->default(0);
            $table->integer('language_id')->nullable();
            $table->integer('currency_id')->nullable();

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
        Schema::drop('bills');
    }

}
