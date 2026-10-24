<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('race_kit_prices', function (Blueprint $table) { $table->id(); $table->foreignId('race_id')->constrained()->cascadeOnDelete(); $table->string('kit_type', 20); $table->decimal('price', 10, 2); $table->string('currency', 3)->default('EGP'); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['race_id', 'kit_type']); }); }
    public function down(): void { Schema::dropIfExists('race_kit_prices'); }
};
