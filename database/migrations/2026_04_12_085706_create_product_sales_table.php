<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('buyer_name', 128);
            $table->string('buyer_phone', 32);
            $table->string('payment_method', 16)->default('cash'); // cash | transfer | qris
            $table->string('payment_status', 16)->default('paid'); // paid | pending
            $table->decimal('total_quantity', 12, 3);
            $table->decimal('total_value', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transacted_at');
            $table->timestamps();

            $table->index('buyer_phone');
            $table->index(['payment_status', 'transacted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sales');
    }
};
