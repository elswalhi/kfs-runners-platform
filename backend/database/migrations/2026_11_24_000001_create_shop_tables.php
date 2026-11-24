<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->text('description')->nullable();
            $table->decimal('price', 10, 2); $table->boolean('is_published')->default(false); $table->timestamps();
        });
        Schema::create('shop_product_images', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->string('path'); $table->unsignedInteger('sort_order')->default(0); $table->timestamps();
        });
        Schema::create('shop_product_variants', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->string('size', 50); $table->string('color', 80); $table->unsignedInteger('stock')->default(0);
            $table->timestamps(); $table->unique(['product_id', 'size', 'color']);
        });
        Schema::create('shop_orders', function (Blueprint $table) {
            $table->id(); $table->string('reference', 64)->unique(); $table->string('name'); $table->string('email');
            $table->string('phone', 32); $table->decimal('total', 10, 2); $table->string('payment_method', 20);
            $table->string('payment_status', 20)->default('pending'); $table->string('status', 20)->default('pending');
            $table->string('receipt_path')->nullable(); $table->timestamps();
        });
        Schema::create('shop_order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained('shop_orders')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('shop_product_variants')->restrictOnDelete();
            $table->string('product_name'); $table->string('size', 50); $table->string('color', 80);
            $table->unsignedInteger('quantity'); $table->decimal('unit_price', 10, 2); $table->timestamps();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('shop_order_id')->nullable()->constrained('shop_orders')->nullOnDelete();
        });
        $id = DB::table('shop_products')->insertGetId(['name' => 'KFS Runners Official Team T-Shirt', 'description' => 'The official KFS Runners team T-shirt.', 'price' => 350, 'is_published' => false, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['front.png', 'back.png', 'front-side.png', 'back-side.png', 'team-shirt-poster.png'] as $i => $file) {
            DB::table('shop_product_images')->insert(['product_id' => $id, 'path' => 'images/shop/team-shirt/'.$file, 'sort_order' => $i, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size) {
            DB::table('shop_product_variants')->insert(['product_id' => $id, 'size' => $size, 'color' => 'Teal', 'stock' => 10, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('shop_order_id'));
        Schema::dropIfExists('shop_order_items'); Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('shop_product_variants'); Schema::dropIfExists('shop_product_images'); Schema::dropIfExists('shop_products');
    }
};
