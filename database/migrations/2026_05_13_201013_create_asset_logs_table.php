<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_logs', function (Blueprint $table) {
            $table->id();
            // Menghubungkan log secara ketat ke ID barang di tabel assets utama
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade');
            $table->date('tanggal')->default(now());
            $table->enum('jenis_log', ['biaya_perawatan', 'perkembangan_fisik', 'vaksin_obat', 'catatan_lainnya']);
            $table->bigInteger('biaya_keluar')->default(0); // Modal tambahan kapitalisasi pupuk/pakan
            $table->string('keterangan'); 
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_logs');
    }
};
