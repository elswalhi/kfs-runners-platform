<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->unsignedInteger('bib_number_start')->default(100);
            $table->unsignedInteger('next_bib_number')->default(100);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->unsignedInteger('bib_number')->nullable();
        });

        foreach (DB::table('races')->orderBy('id')->get(['id', 'distance_label', 'category_type']) as $race) {
            preg_match('/\d+/', (string) $race->distance_label, $matches);
            $distance = (int) ($matches[0] ?? 0);
            $start = $distance === 2 && $race->category_type === 'para'
                ? 250
                : ($distance >= 10 ? $distance * 101 : max(100, $distance * 100));
            $next = $start;

            DB::table('registrations')
                ->where('race_id', $race->id)
                ->orderBy('id')
                ->chunkById(500, function ($registrations) use (&$next) {
                    foreach ($registrations as $registration) {
                        DB::table('registrations')
                            ->where('id', $registration->id)
                            ->update(['bib_number' => $next]);
                        $next++;
                    }
                });

            DB::table('races')->where('id', $race->id)->update([
                'bib_number_start' => $start,
                'next_bib_number' => $next,
            ]);
        }

        Schema::table('registrations', function (Blueprint $table) {
            $table->unique(['race_id', 'bib_number'], 'registrations_race_bib_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique('registrations_race_bib_number_unique');
            $table->dropColumn('bib_number');
        });

        Schema::table('races', function (Blueprint $table) {
            $table->dropColumn(['bib_number_start', 'next_bib_number']);
        });
    }
};
