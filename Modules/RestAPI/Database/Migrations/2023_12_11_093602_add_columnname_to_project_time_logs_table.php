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
        Schema::table('project_time_logs', function (Blueprint $table) {
            $table->integer('client_id')->unsigned()->nullable()->after('project_id');
            $table->foreign('client_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('time_tracker_type')->nullable()->after('client_id');
            $table->integer('team_member_id')->unsigned()->after('time_tracker_type')->nullable();
            $table->foreign('team_member_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('article_id')->unsigned()->after('team_member_id')->nullable();
            $table->foreign('article_id')->references('id')->on('products')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('billable', ['yes', 'no'])->default('no')->after('memo');
            $table->date('time_tracking_date')->after('billable');
            $table->time('client_project_time')->after('time_tracking_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_time_logs', function (Blueprint $table) {
            $table->dropColumn(['client_id','time_tracker_type','team_member_id','article_id','billable','time_tracking_date','client_project_time']);
        });
    }
};
