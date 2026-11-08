<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('event_year');
            $table->boolean('is_current')->default(false)->index()->after('is_published');
            $table->boolean('registration_enabled')->default(false)->after('is_current');
            $table->boolean('payments_enabled')->default(true)->after('registration_enabled');
            $table->boolean('countdown_enabled')->default(true)->after('payments_enabled');
            $table->boolean('is_default')->default(false)->index()->after('countdown_enabled');
            $table->json('qr_fields')->nullable()->after('is_default');
        });

        Schema::table('races', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained('events')->restrictOnDelete();
            $table->index(['event_id', 'is_active', 'sort_order']);
            $table->dropUnique(['slug']);
            $table->unique(['event_id', 'slug']);
        });

        $date = DB::table('app_settings')->where('key', 'race_date')->value('value') ?: '2026-11-20';
        $time = DB::table('app_settings')->where('key', 'race_time')->value('value') ?: '07:00';
        $parsedTimestamp = strtotime($date . ' ' . $time);
        $startsAt = $parsedTimestamp ? date('Y-m-d H:i:s', $parsedTimestamp) : '2026-11-20 07:00:00';
        $now = now();

        $defaultEventId = DB::table('events')->where('slug', 'kfs-marathon-2026')->value('id');
        if ($defaultEventId) {
            DB::table('events')->where('id', $defaultEventId)->update([
                'starts_at' => $startsAt,
                'is_current' => true,
                'registration_enabled' => true,
                'payments_enabled' => true,
                'countdown_enabled' => true,
                'is_default' => true,
                'updated_at' => $now,
            ]);
        } else {
            $defaultEventId = DB::table('events')->insertGetId([
                'title' => 'Kafr El-Sheikh Marathon 2026',
                'title_ar' => 'ماراثون كفر الشيخ 2026',
                'slug' => 'kfs-marathon-2026',
                'event_year' => 2026,
                'starts_at' => $startsAt,
                'location' => 'Kafr El-Sheikh, Egypt',
                'location_ar' => 'كفر الشيخ، مصر',
                'summary' => 'Choose your distance and join the KFS Runners race day.',
                'summary_ar' => 'اختر مسافتك وشارك في يوم سباق عدائي كفر الشيخ.',
                'description' => 'A race day for every pace, every age, and every story.',
                'description_ar' => 'يوم سباق لكل سرعة وعمر وحكاية.',
                'is_published' => true,
                'is_current' => true,
                'registration_enabled' => true,
                'payments_enabled' => true,
                'countdown_enabled' => true,
                'is_default' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('races')->whereNull('event_id')->update(['event_id' => $defaultEventId]);
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'slug']);
            $table->dropIndex(['event_id', 'is_active', 'sort_order']);
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
            $table->unique('slug');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['is_current']);
            $table->dropIndex(['is_default']);
            $table->dropColumn([
                'starts_at', 'is_current', 'registration_enabled', 'payments_enabled',
                'countdown_enabled', 'is_default', 'qr_fields',
            ]);
        });
    }
};
