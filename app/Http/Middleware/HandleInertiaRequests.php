<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $translations = [];
        $path = lang_path($locale . '.json');

        if (is_file($path)) {
            $translations = json_decode((string) file_get_contents($path), true) ?: [];
        }

        return [
            ...parent::share($request),
            'settings' => Setting::all_(),
            'locale' => $locale,
            'translations' => $translations,
            'cartCount' => collect($request->session()->get('cart', []))->sum('qty'),
            'flash' => [
                'ok' => $request->session()->get('ok'),
                'done' => $request->session()->get('done'),
            ],
            'auth' => [
                'admin' => $request->session()->get('admin_id')
                    ? ['id' => $request->session()->get('admin_id')]
                    : null,
            ],
        ];
    }
}
