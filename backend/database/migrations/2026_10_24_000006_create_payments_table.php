<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('payments', function (Blueprint $table) { $table->id(); $table->foreignId('registration_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete(); $table->string('provider', 40)->default('easykash'); $table->decimal('amount', 10, 2); $table->string('currency', 3)->default('EGP'); $table->string('status', 30)->default('pending')->index(); $table->string('transaction_reference')->nullable()->unique(); $table->string('gateway_reference')->nullable()->index(); $table->json('payload')->nullable(); $table->timestamp('verified_at')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('payments'); }
};
