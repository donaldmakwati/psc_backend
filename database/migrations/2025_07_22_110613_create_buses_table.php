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
        Schema::create('buses', function (Blueprint $table) {
            $table->id(); // id BIGINT PRIMARY KEY AUTO_INCREMENT
            $table->string('bus_number', 50)->unique();
            $table->enum('type', ['AC', 'Non-AC', 'Sleeper', 'Seater']);
            $table->integer('capacity');
            $table->enum('status', ['active', 'maintenance'])->default('active');
            $table->timestamps(); // includes created_at and updated_at

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
