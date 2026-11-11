<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroImage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HeroGalleryController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'autoplay' => 'boolean',
            'duration' => 'required|integer|min:2000|max:20000',
            'transition' => ['required', Rule::in(['fade', 'zoom', 'slide'])],
            'speed' => 'required|numeric|min:0.2|max:2',
            'show_arrows' => 'boolean',
            'show_dots' => 'boolean',
            'pause_hover' => 'boolean',
            'images' => 'nullable|array',
            'images.*.id' => 'nullable|integer',
            'images.*.path' => 'required_with:images|string|max:500',
            'images_present' => 'nullable|boolean',
            'new_images' => 'nullable|array',
            'new_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            'main_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $this->syncImages($request);

        Setting::put([
            'hero_autoplay' => $data['autoplay'] ? '1' : '0',
            'hero_duration' => (string) $data['duration'],
            'hero_transition' => $data['transition'],
            'hero_animation_speed' => (string) $data['speed'],
            'hero_show_arrows' => $data['show_arrows'] ? '1' : '0',
            'hero_show_dots' => $data['show_dots'] ? '1' : '0',
            'hero_pause_hover' => $data['pause_hover'] ? '1' : '0',
        ]);

        return back()->with('ok', 'Hero gallery saved');
    }

    private function syncImages(Request $request): void
    {
        $existing = HeroImage::ordered()->get();
        $byId = $existing->keyBy('id');
        $kept = [];
        $order = 0;

        if ($request->boolean('images_present')) {
            foreach ($request->input('images', []) as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $image = $byId->get((int) ($row['id'] ?? 0));

                if (!$image) {
                    continue;
                }

                $image->update(['sort_order' => $order++]);
                $kept[] = $image->id;
            }
        } else {
            foreach ($existing as $image) {
                $image->update(['sort_order' => $order++]);
                $kept[] = $image->id;
            }
        }

        foreach ($existing as $image) {
            if (!in_array($image->id, $kept, true)) {
                $this->deleteFile($image->path);
                $image->delete();
            }
        }

        foreach ($request->file('new_images', []) as $file) {
            HeroImage::create([
                'path' => $file->store('hero', 'public'),
                'sort_order' => $order++,
            ]);
        }

        if ($request->hasFile('main_image')) {
            $main = HeroImage::create([
                'path' => $request->file('main_image')->store('hero', 'public'),
                'sort_order' => 0,
            ]);

            HeroImage::where('id', '!=', $main->id)->increment('sort_order');
        }
    }

    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, 'hero/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
