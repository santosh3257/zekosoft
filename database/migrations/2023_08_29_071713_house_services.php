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
        Schema::create('house_services', function (Blueprint $table) {
            $table->id();
            $table->string('service_name',100)->nullable(false);
            $table->string('service_name_se',100)->nullable(false);
            $table->enum('status',['active','inactive'])->default('active');
            $table->float('tax_rate')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('house_services');
    }
};
