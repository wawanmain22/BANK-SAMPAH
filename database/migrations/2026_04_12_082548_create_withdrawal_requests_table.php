<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('method', 16); // cash | transfer
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
