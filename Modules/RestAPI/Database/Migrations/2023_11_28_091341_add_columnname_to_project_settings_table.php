<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            $table->integer('project_id')->unsigned()->nullable()->after('id');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade')->onUpdate('cascade');
            $table->string('setting_project_type')->nullable()->after('remind_to');
            $table->string('setting_estimate_type')->nullable()->after('setting_project_type');
            $table->string('setting_rate_type')->nullable()->after('setting_estimate_type');
            $table->double('setting_hours_rate')->nullable()->after('setting_rate_type');
            $table->double('setting_cost_rate')->nullable()->after('setting_hours_rate');
            $table->double('setting_expense_grand_total')->nullable()->after('setting_cost_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_settings', function (Blueprint $table) {
            $table->dropColumn(['project_id', 'setting_project_type','setting_estimate_type','setting_rate_type','setting_hours_rate','setting_cost_rate','setting_expense_grand_total']);
        });
    }
};
