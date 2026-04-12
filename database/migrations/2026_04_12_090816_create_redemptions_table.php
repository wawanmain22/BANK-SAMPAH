<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name_snapshot');
            $table->string('unit_snapshot', 16)->default('pcs');
            $table->decimal('quantity', 12, 3);
            $table->integer('points_used');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
    }
};
