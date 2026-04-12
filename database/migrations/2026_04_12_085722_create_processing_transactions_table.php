<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_transactions', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_input_weight', 12, 3)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at')->useCurrent();
            $table->timestamps();

            $table->index('transacted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_transactions');
    }
};
