<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('admin_id')) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::find(session('admin_id'));

        if ($admin && $admin->must_change_password) {
            $allowed = [
                'admin.settings',
                'admin.settings.save',
                'admin.password',
                'admin.logout',
            ];

            if (!$request->routeIs(...$allowed)) {
                return redirect()
                    ->route('admin.settings')
                    ->with('ok', 'Change the default admin password before continuing.');
            }
        }

        return $next($request);
    }
}
