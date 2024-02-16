<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHouseWorkTaxApplicableToEstimateItemsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('estimate_items', 'house_work_tax_applicable')) {
            Schema::table('estimate_items', function (Blueprint $table) {
                $table->integer('house_work_tax_applicable')->after('item_name')->comment('0= Not Calculate, 1 = Calculate')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('estimate_items', function(Blueprint $table)
        {
            $table->dropColumn('house_work_tax_applicable');
        });
    }

}
