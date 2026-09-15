<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // Dipakai RingkasanSaldoSyariah untuk menghitung batas nishab
            // (85 gram emas). Default sama dengan angka yang sebelumnya
            // di-hardcode di widget, supaya tidak ada perubahan perilaku
            // sebelum admin mengubahnya lewat halaman Pengaturan Syariah.
            $table->bigInteger('harga_emas_per_gram')->default(1450000);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
