<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produk;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya membuat 1 data master produk Sepatu dengan stok awal 0
        Produk::create([
            'nama_produk' => 'Sepatu',
            'kode_sku' => 's-1',
            'stok' => 0, 
            'satuan' => 'Pcs',
            'batas_stok_minimum' => 5,
            'harga_beli' => 100000, // Patokan modal Rp 100 Ribu
            'harga_jual' => 150000, // Patokan jual Rp 150 Ribu
        ]);
    }
}
