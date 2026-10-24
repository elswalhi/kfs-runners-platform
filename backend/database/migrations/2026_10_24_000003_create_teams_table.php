<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('teams', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('contact_name'); $table->string('email'); $table->string('phone', 32); $table->string('registration_code')->unique(); $table->string('status', 30)->default('pending'); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('teams'); }
};
