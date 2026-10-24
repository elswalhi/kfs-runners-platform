<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('registrations', function (Blueprint $table) { $table->id(); $table->string('registration_code', 32)->unique(); $table->foreignId('race_id')->constrained()->restrictOnDelete(); $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete(); $table->string('kit_type', 20); $table->string('first_name', 100); $table->string('last_name', 100); $table->string('email'); $table->string('phone', 32); $table->date('birth_date'); $table->string('gender', 30); $table->longText('id_number')->nullable(); $table->string('shirt_size', 10)->nullable(); $table->string('team_name')->nullable(); $table->string('document_path')->nullable(); $table->string('status', 30)->default('pending')->index(); $table->string('payment_status', 30)->default('pending')->index(); $table->decimal('amount', 10, 2); $table->string('currency', 3)->default('EGP'); $table->text('notes')->nullable(); $table->timestamps(); $table->index(['created_at', 'payment_status']); }); }
    public function down(): void { Schema::dropIfExists('registrations'); }
};
