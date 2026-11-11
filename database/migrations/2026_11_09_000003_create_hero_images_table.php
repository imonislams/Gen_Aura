<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_images', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('product_name')->nullable();
            $table->unsignedInteger('product_price')->nullable();
            $table->unsignedInteger('product_old_price')->nullable();
            $table->string('product_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        $now = now();
        $rows = Schema::hasTable('hero_slide_images')
            ? DB::table('hero_slide_images')->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        if ($rows->isNotEmpty()) {
            $order = 0;

            foreach ($rows as $row) {
                DB::table('hero_images')->insert([
                    'path' => $row->path,
                    'product_name' => $row->product_name,
                    'product_price' => $row->product_price,
                    'product_old_price' => $row->product_old_price,
                    'product_url' => $row->product_url,
                    'sort_order' => $order++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return;
        }

        $path = Schema::hasTable('settings')
            ? DB::table('settings')->where('key', 'hero_image')->value('value')
            : null;

        if ($path) {
            DB::table('hero_images')->insert([
                'path' => $path,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_images');
    }
};
