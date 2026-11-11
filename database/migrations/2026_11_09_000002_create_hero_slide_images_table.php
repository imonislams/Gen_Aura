<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slide_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_slide_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('product_name')->nullable();
            $table->unsignedInteger('product_price')->nullable();
            $table->unsignedInteger('product_old_price')->nullable();
            $table->string('product_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        $now = now();

        foreach (DB::table('hero_slides')->orderBy('id')->get() as $slide) {
            $paths = json_decode($slide->images ?? '[]', true);

            if (!is_array($paths)) {
                continue;
            }

            foreach (array_values($paths) as $index => $path) {
                if (!is_string($path) || $path === '') {
                    continue;
                }

                DB::table('hero_slide_images')->insert([
                    'hero_slide_id' => $slide->id,
                    'path' => $path,
                    'product_name' => $index === 0 ? $slide->product_name : null,
                    'product_price' => $index === 0 ? $slide->product_price : null,
                    'product_old_price' => $index === 0 ? $slide->product_old_price : null,
                    'product_url' => $index === 0 ? $slide->product_url : null,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slide_images');
    }
};
