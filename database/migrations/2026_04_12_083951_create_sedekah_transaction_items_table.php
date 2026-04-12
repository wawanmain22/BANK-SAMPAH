<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sedekah_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sedekah_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('waste_category_id')->constrained()->restrictOnDelete();
            $table->string('category_name_snapshot');
            $table->string('unit_snapshot', 16)->default('kg');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->index('waste_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sedekah_transaction_items');
    }
};
