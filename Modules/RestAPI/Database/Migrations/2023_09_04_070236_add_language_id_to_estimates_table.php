<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLanguageIdToEstimatesTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('estimates', 'locale')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->integer('locale')->unsigned()->after('currency_id')->nullable();
                $table->foreign('locale')
                    ->references('id')
                    ->on('language_settings')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
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
        Schema::table('estimates', function(Blueprint $table)
        {
            $table->dropColumn('locale');
        });
    }

}
