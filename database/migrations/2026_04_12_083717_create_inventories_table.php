<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_item_id')->constrained()->cascadeOnDelete();
            $table->string('source', 16); // nabung | sedekah
            $table->decimal('stock', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['waste_item_id', 'source']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
