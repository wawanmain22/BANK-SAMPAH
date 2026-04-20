<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('waste_item_id')->constrained()->restrictOnDelete();
            $table->string('item_code_snapshot', 16);
            $table->string('item_name_snapshot');
            $table->string('category_name_snapshot');
            $table->string('unit_snapshot', 16)->default('kg');
            $table->decimal('price_per_unit', 12, 2);
            $table->decimal('quantity', 12, 3);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();

            $table->index('waste_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_transaction_items');
    }
};
