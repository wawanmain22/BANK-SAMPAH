<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_weight', 12, 3)->default(0);
            $table->decimal('total_value', 14, 2)->default(0);
            $table->integer('points_awarded')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_transactions');
    }
};
