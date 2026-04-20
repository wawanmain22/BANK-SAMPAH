<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_per_unit', 12, 2);
            $table->date('effective_from');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['waste_item_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_prices');
    }
};
