<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVatTypeIdToEstimatesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('estimates', 'vat_type_id')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->integer('vat_type_id')->unsigned()->after('house_services_id')->nullable();
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
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn('vat_type_id');
        });
    }

}
