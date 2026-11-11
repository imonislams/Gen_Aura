<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\HeroImage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    private const SECTIONS = ['general', 'store', 'homepage', 'about', 'social', 'login'];

    private const IMAGES = [
        'general' => ['logo' => 'brand', 'favicon' => 'brand'],
        'homepage' => ['hero_image' => 'hero'],
        'about' => [],
    ];

    private const RULES = [
        'general' => [
            'name' => 'required|max:60',
            'ar' => 'nullable|max:60',
            'phone' => 'nullable|max:30',
            'email' => 'nullable|email|max:120',
            'address' => 'nullable|max:255',
            'wa' => 'nullable|max:20',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:4096',
            'favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,webp,svg|max:1024',
        ],
        'store' => [
            'currency' => 'nullable|max:10',
            'order_prefix' => 'nullable|max:10',
            'cats' => 'required|max:200',
            'cod_enabled' => 'nullable|in:0,1',
            'stock_display' => 'nullable|in:0,1',
            'low_stock_threshold' => 'nullable|integer|min:0|max:1000',
        ],
        'homepage' => [
            'top' => 'nullable|max:120',
            'title' => 'nullable|max:120',
            'sub' => 'nullable|max:300',
            'promo_title' => 'nullable|max:120',
            'promo_text' => 'nullable|max:300',
            'why_choose_us' => 'nullable|max:300',
            'hero_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ],
        'about' => [
            'about_title' => 'nullable|max:120',
            'about_body' => 'nullable|max:2000',
            'about_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
            'about_images' => 'nullable|array|max:30',
            'about_images.*' => 'string|max:500',
            'new_about_images' => 'nullable|array|max:20',
            'new_about_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'replace_about_images' => 'nullable|array|max:30',
            'replace_about_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'about_images_present' => 'nullable|boolean',
        ],
        'social' => [
            'fb' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'tiktok' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
        ],
    ];

    private const LABELS = [
        'general' => 'General',
        'store' => 'Store',
        'homepage' => 'Homepage',
        'about' => 'About',
        'social' => 'Social links',
        'login' => 'Admin login',
    ];

    public function show(string $section)
    {
        abort_unless(in_array($section, self::SECTIONS, true), 404);

        $props = [
            'section' => $section,
            'adminUsername' => Admin::find(session('admin_id'))?->username,
        ];

        if ($section === 'about') {
            $props['aboutImages'] = collect(Setting::aboutImages())->map(fn ($path) => [
                'path' => $path,
                'url' => asset('storage/' . $path),
            ])->values();
        }

        if ($section === 'homepage') {
            $settings = Setting::all_();

            $props['heroImages'] = HeroImage::ordered()->get()->map(fn (HeroImage $image) => [
                'id' => $image->id,
                'path' => $image->path,
                'url' => asset('storage/' . $image->path),
            ])->values();

            $props['heroSettings'] = [
                'autoplay' => ($settings['hero_autoplay'] ?? '1') === '1',
                'duration' => (int) ($settings['hero_duration'] ?? 6500),
                'transition' => in_array($settings['hero_transition'] ?? 'fade', ['fade', 'zoom', 'slide'], true)
                    ? $settings['hero_transition']
                    : 'fade',
                'speed' => (float) ($settings['hero_animation_speed'] ?? 0.7),
                'show_arrows' => ($settings['hero_show_arrows'] ?? '1') === '1',
                'show_dots' => ($settings['hero_show_dots'] ?? '1') === '1',
                'pause_hover' => ($settings['hero_pause_hover'] ?? '1') === '1',
            ];

        }

        return Inertia::render('Admin/Settings/' . ucfirst($section), $props);
    }

    public function save(Request $r, string $section)
    {
        abort_unless(isset(self::RULES[$section]), 404);

        $d = $r->validate(self::RULES[$section]);

        if ($section === 'about') {
            $d = $this->syncAboutImages($r, $d);
        }

        foreach (self::IMAGES[$section] ?? [] as $field => $dir) {
            unset($d[$field]);

            if ($r->hasFile($field)) {
                $old = Setting::all_()[$field] ?? '';
                if ($old && str_starts_with($old, $dir . '/')) {
                    Storage::disk('public')->delete($old);
                }
                $d[$field] = $r->file($field)->store($dir, 'public');
            }
        }

        Setting::put($d);

        return back()->with('ok', self::LABELS[$section] . ' settings saved');
    }

    private function syncAboutImages(Request $r, array $d): array
    {
        $settings = Setting::all_();
        $oldFallback = (string) ($settings['about_image'] ?? '');
        $current = json_decode((string) ($settings['about_images'] ?? '[]'), true);

        if (!is_array($current)) {
            $current = [];
        }

        if (empty($current) && $oldFallback) {
            $current = [$oldFallback];
        }

        $newFallback = '';

        if ($r->hasFile('about_image')) {
            $newFallback = $r->file('about_image')->store('about', 'public');
        }

        $newFiles = $r->file('new_about_images', []);
        $replacements = $r->file('replace_about_images', []);
        $tokens = $r->boolean('about_images_present') ? $r->input('about_images', []) : $current;

        $gallery = [];
        $replacedOld = [];

        foreach ($tokens as $index => $token) {
            if (!is_string($token)) {
                continue;
            }

            if (preg_match('/^__new_(\d+)__$/', $token, $match)) {
                $newIndex = (int) $match[1];

                if (isset($newFiles[$newIndex])) {
                    $gallery[] = $newFiles[$newIndex]->store('about', 'public');
                }

                continue;
            }

            if (!in_array($token, $current, true)) {
                continue;
            }

            $replacement = $replacements[$index] ?? null;

            if ($replacement instanceof \Illuminate\Http\UploadedFile) {
                $gallery[] = $replacement->store('about', 'public');
                $replacedOld[] = $token;
            } else {
                $gallery[] = $token;
            }
        }

        $gallery = array_values(array_unique($gallery));
        $gallery = array_slice($gallery, 0, 20);
        $protected = array_merge($gallery, $newFallback ? [$newFallback] : []);

        foreach (array_unique(array_merge($current, $replacedOld)) as $path) {
            if (!in_array($path, $protected, true)) {
                $this->deleteAboutFile($path);
            }
        }

        $d['about_images'] = json_encode($gallery);
        $d['about_image'] = $newFallback;

        unset($d['new_about_images'], $d['replace_about_images'], $d['about_images_present']);

        return $d;
    }

    private function deleteAboutFile(string $path): void
    {
        if ($path !== '' && str_starts_with($path, 'about/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
