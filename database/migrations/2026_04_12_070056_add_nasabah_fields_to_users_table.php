<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->boolean('is_member')->default(false)->after('address');
            $table->date('member_joined_at')->nullable()->after('is_member');

            $table->index('is_member');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_member']);
            $table->dropColumn(['phone', 'address', 'is_member', 'member_joined_at']);
        });
    }
};
