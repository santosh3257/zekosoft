<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHouseWorkIdToEstimateItemsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('estimate_items', 'house_work_id')) {
            Schema::table('estimate_items', function (Blueprint $table) {
                $table->integer('house_work_id')->unsigned()->after('estimate_id')->nullable();
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
            $table->dropColumn('house_work_id');
        });
    }

}
