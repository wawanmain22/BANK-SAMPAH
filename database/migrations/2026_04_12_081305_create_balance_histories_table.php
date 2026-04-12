<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bucket', 32); // tertahan | tersedia
            $table->string('type', 32);   // nabung | release | withdrawal | adjustment
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->nullableMorphs('source');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'bucket', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_histories');
    }
};
