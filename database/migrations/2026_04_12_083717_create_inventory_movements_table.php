<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_category_id')->constrained()->restrictOnDelete();
            $table->string('direction', 8); // in | out
            $table->string('reason', 32);   // nabung | sedekah | sale | process | adjustment
            $table->decimal('quantity', 14, 3);
            $table->decimal('stock_after', 14, 3);
            $table->nullableMorphs('source');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['waste_category_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
