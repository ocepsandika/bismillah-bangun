<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->boolean('is_saldo_awal')->default(false);
            
            // Klasifikasi Jenis Harta Syariah
            $table->enum('jenis', ['hewan_ternak', 'tanah_properti', 'emas_logam', 'pertanian_perkebunan', 'lainnya'])->default('lainnya')->index();
            $table->string('sub_jenis', 50)->nullable()->index(); 
            $table->enum('gender', ['jantan', 'betina', 'tidak_berlaku'])->default('tidak_berlaku');
            $table->enum('peruntukan', ['qunyah', 'tijarah'])->default('qunyah')->index();
            
            // Riwayat Waktu Kepemilikan (Penting untuk Hisab Haul Zakat)
            $table->date('tanggal_beli')->default(now())->index(); 
            $table->date('tanggal_jual')->nullable()->index(); 
            
            // Kuantitas & Porsi Kemitraan (Syirkah Patungan)
            $table->decimal('jumlah', 12, 2)->default(1.00); 
            $table->string('satuan', 20)->default('unit'); 
            $table->integer('persentase_milik_pribadi')->default(100); 

            // Valuasi Keuangan & Kalkulator Laba Bersih Otomatis
            $table->bigInteger('harga_beli'); 
            $table->bigInteger('nilai_pasar_sekarang'); 
            $table->timestamp('nilai_pasar_updated_at')->nullable(); // ← BARU
            $table->bigInteger('keuntungan')->default(0); 
            
            // Status Operasional Fisik Lapangan
            $table->enum('status_aset', ['aktif', 'lahir_di_kandang', 'terjual', 'mati_rusak', 'dikonsumsi'])->default('aktif')->index();

            $table->string('lokasi_keterangan')->nullable(); 
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
