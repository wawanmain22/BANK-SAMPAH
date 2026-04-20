<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_cash_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('point_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('points_used');
            $table->decimal('rate_snapshot', 12, 2);   // rupiah_per_point at time of cashout
            $table->decimal('cash_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cashed_out_at');
            $table->timestamps();

            $table->index(['user_id', 'cashed_out_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_cash_outs');
    }
};
