<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained()->restrictOnDelete();
            $table->decimal('total_weight', 12, 3)->default(0);
            $table->decimal('total_value', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at')->useCurrent();
            $table->timestamps();

            $table->index(['partner_id', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
