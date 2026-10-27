<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('events')
            ->whereIn('slug', [
                'half-marathon-2021',
                'half-marathon-2022',
                'half-marathon-2023',
            ])
            ->update(['is_published' => true]);
    }

    public function down(): void
    {
        DB::table('events')
            ->whereIn('slug', [
                'half-marathon-2021',
                'half-marathon-2022',
                'half-marathon-2023',
            ])
            ->update(['is_published' => false]);
    }
};
