<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ContactMessage;
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

    public function messages(Request $r)
    {
        $q = ContactMessage::query()->latest();

        if ($r->filled('status')) {
            $q->where('status', $r->input('status'));
        }

        if ($r->filled('q')) {
            $term = '%' . $r->input('q') . '%';
            $q->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('subject', 'like', $term)
                    ->orWhere('message', 'like', $term);
            });
        }

        return Inertia::render('Admin/Messages/Index', [
            'messages' => $q->paginate(30)->withQueryString()->through(fn (ContactMessage $message) => [
                'id' => $message->id,
                'name' => $message->name,
                'phone' => $message->phone,
                'email' => $message->email,
                'subject' => $message->subject,
                'message' => $message->message,
                'status' => $message->status,
                'created_at' => $message->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'status' => $r->input('status'),
                'q' => $r->input('q'),
            ],
            'statuses' => ['new', 'read', 'archived'],
            'unread' => ContactMessage::where('status', 'new')->count(),
        ]);
    }

    public function messageStatus(Request $r, ContactMessage $message)
    {
        $d = $r->validate(['status' => ['required', Rule::in(['new', 'read', 'archived'])]]);
        $message->update(['status' => $d['status']]);

        return back()->with('ok', 'Message ' . $d['status']);
    }

    public function messageDestroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('ok', 'Message deleted');
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
            'name' => 'required|max:60', 'ar' => 'nullable|max:60', 'top' => 'nullable|max:120',
            'title' => 'nullable|max:120', 'sub' => 'nullable|max:300',
            'wa' => 'nullable|max:20', 'phone' => 'nullable|max:30', 'email' => 'nullable|email|max:120', 'address' => 'nullable|max:255',
            'ship' => 'required|integer|min:0', 'cats' => 'required|max:200',
            'fb' => 'nullable|max:255', 'instagram' => 'nullable|max:255', 'youtube' => 'nullable|max:255',
            'currency' => 'nullable|max:10', 'order_prefix' => 'nullable|max:10',
            'cod_enabled' => 'nullable|in:0,1', 'stock_display' => 'nullable|in:0,1',
            'promo_title' => 'nullable|max:120', 'promo_text' => 'nullable|max:300',
            'why_choose_us' => 'nullable|max:300',
            'about_title' => 'nullable|max:120', 'about_body' => 'nullable|max:2000',
            'hero_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'logo' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:4096',
            'favicon' => 'nullable|file|mimes:ico,png,jpg,jpeg,webp,svg|max:1024',
            'about_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        foreach (['hero_image' => 'hero', 'logo' => 'brand', 'favicon' => 'brand', 'about_image' => 'about'] as $field => $dir) {
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

        return back()->with('ok', 'Settings saved');
    }

    public function password(Request $r)
    {
        $d = $r->validate(['username' => 'required|max:40', 'current' => 'required', 'new_password' => 'required|min:6|max:100']);
        $a = Admin::find(session('admin_id'));
        if (!Hash::check($d['current'], $a->password)) return back()->withErrors('Current password is wrong');
        $a->update(['username' => $d['username'], 'password' => $d['new_password'], 'must_change_password' => false]);
        return back()->with('ok', 'Login details updated');
    }
}
