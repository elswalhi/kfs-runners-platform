<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('registrations')
            ->where('payment_status', '!=', 'paid')
            ->whereNotNull('bib_number')
            ->update(['bib_number' => null]);
    }

    public function down(): void
    {
        // Issued numbers cannot be restored reliably after the payment rule changes.
    }
};
