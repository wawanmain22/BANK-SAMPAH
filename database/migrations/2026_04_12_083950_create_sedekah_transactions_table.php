<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sedekah_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('donor_name')->nullable();
            $table->decimal('total_weight', 12, 3)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at')->useCurrent();
            $table->timestamps();

            $table->index('transacted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sedekah_transactions');
    }
};
