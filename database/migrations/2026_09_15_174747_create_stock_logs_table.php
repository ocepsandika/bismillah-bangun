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
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();

             $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();
 
            // null untuk penyesuaian manual (stok opname, barang rusak, dst)
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
 
            // siapa yang memicu perubahan (kasir yang input transaksi, atau
            // admin yang melakukan penyesuaian manual). null = sistem/tidak diketahui.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
 
            // penjualan, kulakan, koreksi_edit, dihapus, dihapus_permanen,
            // dipulihkan, penyesuaian_manual
            $table->string('jenis', 40)->index();
 
            // positif = stok bertambah, negatif = stok berkurang
            $table->integer('perubahan');
            $table->integer('stok_sebelum');
            $table->integer('stok_sesudah');
 
            $table->string('keterangan')->nullable();

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};
