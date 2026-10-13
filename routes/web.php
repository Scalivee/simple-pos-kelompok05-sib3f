<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('pos.create');
});

/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
| Hanya bisa diakses saat belum login
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->name('login.store');

    // Halaman informasi khusus tamu
    Route::get('/info', function () {
        return view('info');
    })->name('info');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| User yang sudah login
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard - pertahankan kalau memang masih digunakan
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    // Kasir
    Route::get('/pos', [TransactionController::class, 'create'])
        ->name('pos.create');

    Route::post('/pos', [TransactionController::class, 'store'])
        ->name('transactions.store');

    // Riwayat transaksi
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->name('transactions.index');

    // Pertahankan jika fitur detail transaksi masih digunakan
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
        ->name('transactions.show');

    /*
    |--------------------------------------------------------------------------
    | Khusus Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {

        Route::resource('categories', CategoryController::class)
            ->except('show');

        Route::resource('products', ProductController::class)
            ->except(['show', 'destroy']);
    });
});