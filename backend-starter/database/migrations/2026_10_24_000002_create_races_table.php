<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('races', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('distance_label', 40); $table->string('category_type', 30)->default('individual'); $table->unsignedSmallInteger('sort_order')->default(0); $table->boolean('is_active')->default(true); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('races'); }
};
