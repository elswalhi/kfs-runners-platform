<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bus_bookings', function (Blueprint $table) {
            $table->string('email', 190)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Existing wallet bookings may not have an email address.
    }
};
