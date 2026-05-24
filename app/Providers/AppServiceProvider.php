<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Transaction; // 🌟 Wajib panggil Model Transaksi Anda
use App\Observers\TransactionObserver; // 🌟 Wajib panggil Observer Transaksi Anda

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 🚀 AKTIFKAN SINKRONISASI STOK OTOMATIS DI SINI:
        Transaction::observe(TransactionObserver::class);
    }
}
