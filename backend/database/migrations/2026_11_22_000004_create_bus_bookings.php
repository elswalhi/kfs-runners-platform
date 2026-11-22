<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bus_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();
            $table->string('name', 150);
            $table->string('email', 190);
            $table->string('phone', 32);
            $table->decimal('amount', 10, 2);
            $table->string('payment_status', 30)->default('pending')->index();
            $table->timestamps();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('bus_booking_id')->nullable()->constrained('bus_bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bus_booking_id');
        });
        Schema::dropIfExists('bus_bookings');
    }
};
