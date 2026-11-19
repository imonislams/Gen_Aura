<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_variants')->default(false)->after('size_stock')->index();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id')->index();
            $table->string('color', 60)->nullable()->after('size');
            $table->string('sku', 60)->nullable()->after('color');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id')->index();
            $table->string('color', 60)->nullable()->after('size');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('has_variants');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'color', 'sku']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'color']);
        });
    }
};
