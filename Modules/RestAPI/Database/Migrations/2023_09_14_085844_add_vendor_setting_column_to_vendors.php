<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVendorSettingColumnToVendors extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vendors', function(Blueprint $table)
        {
            $table->integer('archive')->after('status')->default(0);
            $table->integer('setting_language')->nullable()->after('tax_id');
            $table->integer('setting_currency')->nullable()->after('tax_id');
            $table->integer('setting_vat')->nullable()->after('tax_id');
            $table->integer('setting_tax_code')->nullable()->after('tax_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendors', function(Blueprint $table)
        {
            $table->dropColumn('archive');
            $table->dropColumn('setting_language');
            $table->dropColumn('setting_currency');
            $table->dropColumn('setting_vat');
            $table->dropColumn('setting_tax_code');
        });
    }

}
