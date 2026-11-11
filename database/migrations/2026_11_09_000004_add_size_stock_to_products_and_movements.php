<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('size_stock')->nullable()->after('stock');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('size', 20)->nullable()->after('quantity_delta')->index();
            $table->unsignedInteger('size_stock_before')->nullable()->after('size');
            $table->unsignedInteger('size_stock_after')->nullable()->after('size_stock_before');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['size', 'size_stock_before', 'size_stock_after']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size_stock');
        });
    }
};
