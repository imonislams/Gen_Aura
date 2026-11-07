<?php
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MiscController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ShopController;
use App\Http\Middleware\AdminAuth;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware([SetLocale::class])->group(function () {
    Route::get('/', [ShopController::class, 'home'])->name('home');
    Route::get('/shop', [ShopController::class, 'shop'])->name('shop');
    Route::get('/product/{product}', [ShopController::class, 'product'])->name('product');
    Route::get('/reviews', [ShopController::class, 'reviews'])->name('reviews');
    Route::post('/reviews', [ShopController::class, 'storeReview'])->middleware('throttle:10,1')->name('reviews.store');
    Route::get('/contact', [ShopController::class, 'contact'])->name('contact');
    Route::get('/lang/{l}', [ShopController::class, 'lang'])->name('lang');

    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/checkout', [CartController::class, 'checkout'])->middleware('throttle:10,1')->name('cart.checkout');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
    Route::get('/order/{order}/success', [CheckoutController::class, 'success'])->middleware('signed')->name('order.success');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('login', [AuthController::class, 'show'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.post');
        Route::middleware(AdminAuth::class)->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::resource('products', AdminProductController::class)->except('show');
            Route::patch('products/{product}/restore', [AdminProductController::class, 'restore'])->name('products.restore')->withTrashed();
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
            Route::get('orders', [OrderController::class, 'index'])->name('orders');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
            Route::patch('orders/{order}', [OrderController::class, 'update'])->name('orders.update');
            Route::delete('orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
            Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
            Route::prefix('analytics')->name('analytics.')->group(function () {
                Route::get('/', [AnalyticsController::class, 'overview'])->name('overview');
                Route::get('live', [AnalyticsController::class, 'live'])->name('live');
                Route::get('live/data', [AnalyticsController::class, 'liveData'])->name('live.data')->middleware('throttle:60,1');
                Route::get('visitors', [AnalyticsController::class, 'visitors'])->name('visitors');
                Route::get('visitors/{uuid}', [AnalyticsController::class, 'visitor'])->name('visitor');
                Route::get('activity', [AnalyticsController::class, 'activity'])->name('activity');
                Route::get('products', [AnalyticsController::class, 'products'])->name('products');
                Route::get('products/{product}', [AnalyticsController::class, 'product'])->name('product');
                Route::get('cart', [AnalyticsController::class, 'cart'])->name('cart');
                Route::get('checkout', [AnalyticsController::class, 'checkout'])->name('checkout');
                Route::get('funnel', [AnalyticsController::class, 'funnel'])->name('funnel');
                Route::get('reports', [AnalyticsController::class, 'reports'])->name('reports');
            });
            Route::get('reviews', [MiscController::class, 'reviews'])->name('reviews');
            Route::patch('reviews/{review}/status', [MiscController::class, 'reviewStatus'])->name('reviews.status');
            Route::delete('reviews/{review}', [MiscController::class, 'reviewDestroy'])->name('reviews.destroy');
            Route::get('settings', [MiscController::class, 'settings'])->name('settings');
            Route::post('settings', [MiscController::class, 'settingsSave'])->name('settings.save');
            Route::post('password', [MiscController::class, 'password'])->name('password');
        });
    });
});
