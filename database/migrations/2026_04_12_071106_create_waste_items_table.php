<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_category_id')->constrained()->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit', 16)->default('kg');
            $table->decimal('price_per_unit', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['waste_category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_items');
    }
};
