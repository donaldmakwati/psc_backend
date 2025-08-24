<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the `payments` table to record all transactions.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // A payment can exist before a ticket is created.
            // This field will be updated later when a ticket is generated.
            $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('cascade');
            
            // Link the payment to the trip it belongs to.
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            
            // Link the payment to the user (staff) who created it.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->decimal('amount', 8, 2);
            $table->string('method'); // e.g., 'cash', 'ecocash', 'zb', 'cbz'
            $table->string('status')->default('pending'); // e.g., 'pending', 'completed', 'failed'
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
