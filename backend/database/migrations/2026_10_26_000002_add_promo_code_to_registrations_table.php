<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('promo_code_id')->nullable()->after('race_id')->constrained('promo_codes')->nullOnDelete();
            $table->string('promo_code', 64)->nullable()->after('promo_code_id');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->after('amount');
            $table->decimal('original_amount', 10, 2)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['promo_code_id']);
            $table->dropColumn(['promo_code_id', 'promo_code', 'discount_amount', 'original_amount']);
        });
    }
};
