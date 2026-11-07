<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Review;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MiscController extends Controller
{
    public function reviews(Request $r)
    {
        $q = Review::with('product')->latest();
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('q')) $q->where(fn ($x) => $x->where('name', 'like', '%' . $r->q . '%')->orWhere('text', 'like', '%' . $r->q . '%'));

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $q->with('product')->latest()->paginate(30)->withQueryString()->through(fn (Review $review) => [
                'id' => $review->id,
                'name' => $review->name,
                'text' => $review->text,
                'stars' => $review->stars,
                'status' => $review->status,
                'product' => $review->product?->name,
                'created_at' => $review->created_at?->toIso8601String(),
            ]),
            'filters' => $r->only(['status', 'q']),
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function reviewStatus(Request $r, Review $review)
    {
        $d = $r->validate(['status' => ['required', Rule::in(['pending', 'approved', 'rejected'])]]);
        $review->update(['status' => $d['status']]);

        return back()->with('ok', 'Review ' . $d['status']);
    }

    public function reviewDestroy(Review $review)
    {
        $review->delete();
        return back()->with('ok', 'Review deleted');
    }

    public function settings()
    {
        return Inertia::render('Admin/Settings/Index', [
            'adminUsername' => Admin::find(session('admin_id'))?->username,
        ]);
    }

    public function settingsSave(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|max:60', 'ar' => 'nullable|max:60', 'top' => 'nullable|max:120', 'title' => 'nullable|max:120', 'sub' => 'nullable|max:300',
            'wa' => 'nullable|max:20', 'ship' => 'required|integer|min:0', 'cats' => 'required|max:200', 'fb' => 'nullable|max:200',
            'hero_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);
        unset($d['hero_image']);
        if ($r->hasFile('hero_image')) {
            $old = Setting::all_()['hero_image'] ?? '';
            if ($old && str_starts_with($old, 'hero/')) Storage::disk('public')->delete($old);
            $d['hero_image'] = $r->file('hero_image')->store('hero', 'public');
        }
        Setting::put($d);
        return back()->with('ok', 'Settings saved');
    }

    public function password(Request $r)
    {
        $d = $r->validate(['username' => 'required|max:40', 'current' => 'required', 'new_password' => 'required|min:6|max:100']);
        $a = Admin::find(session('admin_id'));
        if (!Hash::check($d['current'], $a->password)) return back()->withErrors('Current password is wrong');
        $a->update(['username' => $d['username'], 'password' => $d['new_password']]);
        return back()->with('ok', 'Login details updated');
    }
}
