<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            
            // Identitas & Stok Utama
            $table->string('nama_produk'); // Nama barang dagangan (cth: Sabun, Baju)
            $table->string('kode_sku')->unique()->nullable(); // Barcode unik (bisa dikosongkan jika eceran)
            $table->integer('stok')->default(0); // Sisa jumlah barang di toko
            $table->string('satuan', 20)->default('Pcs'); // Satuan barang (cth: Pcs, Dus, Kg)
            $table->integer('batas_stok_minimum')->default(5); // Alarm otomatis jika stok menipis
            
            // Keuangan Bulat Format Rupiah
            $table->bigInteger('harga_beli')->default(0); // Modal awal kulakan barang
            $table->bigInteger('harga_jual')->default(0); // Harga jual ke konsumen
            
            $table->timestamps();
            $table->softDeletes(); // Fitur hapus aman (tidak langsung hilang permanen)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
