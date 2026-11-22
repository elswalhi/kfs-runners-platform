<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('runner_account_id')
                ->nullable()
                ->index()
                ->constrained('runner_accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(['runner_account_id']);
            $table->dropIndex(['runner_account_id']);
            $table->dropColumn('runner_account_id');
        });
    }
};
