<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama kategori spesifik
            $table->boolean('is_expense')->default(true); // Pemasukan (false) / Pengeluaran (true)
            
            // Kolom klasifikasi 3 pilar utama keuangan Islami Anda
            $table->enum('type', ['rumah_tangga', 'tijarah', 'tabarru'])
                  ->default('rumah_tangga');

            $table->timestamps();
            $table->softDeletes(); // Mengamankan data dari penghapusan tidak sengaja [laravel.com]
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
