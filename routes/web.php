<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Marketing / public pages
Route::view('/', 'welcome')->name('home');
Route::view('/how-it-works', 'pages.how-it-works')->name('how-it-works');
Route::view('/merchants', 'pages.merchants')->name('merchants');
Route::view('/pricing', 'pages.pricing')->name('pricing');

// Logout (used by the app-shell profile menu).
Route::post('logout', function (Logout $logout) {
    $logout();

    return redirect('/');
})->middleware('auth')->name('logout');

// Type-aware dashboard: merchants and buyers see different homes.
Route::get('dashboard', function () {
    return Auth::user()->isMerchant()
        ? view('dashboard.merchant')
        : view('dashboard.buyer');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Per-request chat — shared by the buyer and the merchant participant.
Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('conversations/{conversation}', 'conversations.show')->name('conversations.show');
    Volt::route('leaderboard', 'leaderboard')->name('leaderboard');
    Volt::route('notifications', 'notifications.index')->name('notifications.index');

    // Auctions — any user can list an item; anyone can bid it up.
    Volt::route('auctions', 'auctions.index')->name('auctions.index');
    Volt::route('auctions/create', 'auctions.create')->name('auctions.create');
    Volt::route('auctions/{auction}', 'auctions.show')->name('auctions.show');

    // Web Push subscription management
    Route::post('push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

// Buyer-only area — publishing and managing demand requests.
Route::middleware(['auth', 'verified', 'user.type:buyer'])
    ->prefix('requests')
    ->name('requests.')
    ->group(function () {
        Volt::route('/', 'requests.index')->name('index');
        Volt::route('/create', 'requests.create')->name('create');
        Volt::route('/{request}', 'requests.show')->name('show');
        Volt::route('/{request}/edit', 'requests.edit')->name('edit');
    });

// Admin-only area.
Route::middleware(['auth', 'verified', 'user.type:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Volt::route('/', 'admin.dashboard')->name('dashboard');
        Volt::route('merchants', 'admin.merchants')->name('merchants');
        Volt::route('users', 'admin.users')->name('users');
        Volt::route('requests', 'admin.requests')->name('requests');
        Volt::route('plans', 'admin.plans')->name('plans');
        Volt::route('payments', 'admin.payments')->name('payments');
        Volt::route('demand', 'admin.demand')->name('demand');
    });

// Merchant-only area (leads, offers, credits — built out in later phases).
Route::middleware(['auth', 'verified', 'user.type:merchant'])
    ->prefix('merchant')
    ->name('merchant.')
    ->group(function () {
        Volt::route('leads', 'merchant.leads.index')->name('leads.index');
        Volt::route('leads/{lead}', 'merchant.leads.show')->name('leads.show');
        Volt::route('billing', 'merchant.billing')->name('billing');
        Volt::route('checkout/{kind}/{key}', 'merchant.checkout')->name('checkout');
    });

require __DIR__.'/auth.php';
