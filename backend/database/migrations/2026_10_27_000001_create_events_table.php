<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 180);
            $table->string('title_ar', 180)->nullable();
            $table->string('slug', 200)->unique();
            $table->unsignedSmallInteger('event_year');
            $table->string('location', 180)->nullable();
            $table->string('location_ar', 180)->nullable();
            $table->string('summary', 280)->nullable();
            $table->string('summary_ar', 280)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_ar')->nullable();
            $table->string('registration_url', 2048)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
