<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            // Menghubungkan secara ketat ke tabel categories yang sudah ada
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            
            // MENGUBAH BULAN_TAHUN MENJADI RENTANG TANGGAL
            $table->date('start_date'); // Tanggal mulai periode anggaran
            $table->date('end_date');   // Tanggal selesai periode anggaran
            
            $table->bigInteger('plafon_anggaran'); // Batas maksimal uang yang dialokasikan (misal: Rp3.000.000)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
