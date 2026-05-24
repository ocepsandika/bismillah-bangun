<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use SoftDeletes; // Mengaktifkan fitur hapus aman agar sinkron dengan database

    protected $table = 'produks'; // Mengunci nama tabel bahasa Indonesia pilihan Anda

    // Mendaftarkan semua kolom agar diizinkan disimpan oleh sistem Filament
    protected $fillable = [
        'nama_produk',
        'kode_sku',
        'harga_beli',
        'harga_jual',
        'stok',
        'satuan',
        'batas_stok_minimum',
    ];

    /**
     * Relasi ke Tabel Transaksi Utama
     */
    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaction::class, 'produk_id');
    }
}
