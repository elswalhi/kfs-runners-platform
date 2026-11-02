<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('age_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedTinyInteger('min_age');
            $table->unsignedTinyInteger('max_age');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['min_age', 'max_age']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('age_groups');
    }
};
