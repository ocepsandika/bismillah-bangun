<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // === 1. PILAR TABARRU' (SOSIAL, HAK AGAMA & PEMBERSIHAN HARTA WARA') ===
            ['name' => 'Zakat Maal (Harta)', 'is_expense' => true, 'type' => 'tabarru'],
            ['name' => 'Zakat Fitrah', 'is_expense' => true, 'type' => 'tabarru'],
            ['name' => 'Infaq & Sedekah Rutin', 'is_expense' => true, 'type' => 'tabarru'],
            ['name' => 'Wakaf Tunai', 'is_expense' => true, 'type' => 'tabarru'],
            ['name' => 'Fidyah & Kafarat', 'is_expense' => true, 'type' => 'tabarru'],
            ['name' => 'Hadiah & Hibah Keluar', 'is_expense' => true, 'type' => 'tabarru'],
            ['name' => 'Penerimaan Hadiah / Hibah Masuk', 'is_expense' => false, 'type' => 'tabarru'],
            ['name' => 'Penerimaan Dana Syubhat / Non-Halal', 'is_expense' => false, 'type' => 'tabarru'], 
            ['name' => 'Pembersihan Harta (Fasilitas Umum)', 'is_expense' => true, 'type' => 'tabarru'], 
            ['name' => 'Dana Talangan Sosial (Qardhul Hasan)', 'is_expense' => true, 'type' => 'tabarru'], 

            // === 2. PILAR RUMAH TANGGA (DOMESTIK & AL-MA'ISYAH WAJIB) ===
            ['name' => 'Nafkah Mutlak Istri', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Belanja Dapur & Sembako', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Listrik, Air & Keamanan', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Pendidikan & Sekolah Anak', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Kesehatan & Obat-obatan', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Bensin & Servis Kendaraan', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Kuota Internet & Pulsa', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Gaji Bulanan Suami (Pemasukan)', 'is_expense' => false, 'type' => 'rumah_tangga'],
            ['name' => 'Pendapatan Sampingan Rumah Tangga', 'is_expense' => false, 'type' => 'rumah_tangga'],
            
            // Tambahan dari Google Sheet & Logika Modal (Tetap aman di bawah pilar ke-2)
            ['name' => 'Personal / Keperluan Pribadi', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Beauty & Apparel / Pakaian', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Self Dev / Pengembangan Diri', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Travel / Hiburan & Liburan', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Pets / Hewan Peliharaan', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Bonus / THR', 'is_expense' => false, 'type' => 'rumah_tangga'],
            ['name' => 'Investasi / Setoran Modal ke Bisnis', 'is_expense' => true, 'type' => 'rumah_tangga'],
            
            // Pos Utang-Piutang Pribadi (Domestik)
            ['name' => 'Penerimaan Pinjaman Utang Masuk', 'is_expense' => false, 'type' => 'rumah_tangga'],
            ['name' => 'Pembayaran Cicilan/Pelunasan Utang', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Pengeluaran Meminjamkan Uang ke Orang', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Penerimaan Pelunasan Piutang dari Orang', 'is_expense' => false, 'type' => 'rumah_tangga'],

            // Akumulasi Aset Pribadi/Konsumsi (Tanah/Emas Simpanan Pribadi)
            ['name' => 'Pembelian Aset Simpanan Pribadi (Emas/Tanah/Ternak)', 'is_expense' => true, 'type' => 'rumah_tangga'],
            ['name' => 'Penjualan Aset Simpanan Pribadi (Emas/Tanah/Ternak)', 'is_expense' => false, 'type' => 'rumah_tangga'],

            // === 3. PILAR TIJARAH (BISNIS, INVESTASI & PRODUKTIF KERSEN) ===
            ['name' => 'Pemasukan Omset Usaha Bersih', 'is_expense' => false, 'type' => 'tijarah'],
            ['name' => 'Bagi Hasil Investasi (Syirkah)', 'is_expense' => false, 'type' => 'tijarah'],
            ['name' => 'Modal Usaha Baru', 'is_expense' => true, 'type' => 'tijarah'],
            ['name' => 'Biaya Operasional Bisnis', 'is_expense' => true, 'type' => 'tijarah'],
            ['name' => 'Gaji Karyawan / Reseller', 'is_expense' => true, 'type' => 'tijarah'],
            ['name' => 'Pendapatan Usaha Syubhat (Isolasi)', 'is_expense' => false, 'type' => 'tijarah'], 
            ['name' => 'Retur / Pengembalian Hak Konsumen', 'is_expense' => true, 'type' => 'tijarah'],

            // Pos Utang-Piutang & Aset khusus Bisnis/Perdagangan
            ['name' => 'Penjualan Barang Secara Tempo/Kredit (Piutang Dagang)', 'is_expense' => false, 'type' => 'tijarah'],
            ['name' => 'Penerimaan Setoran Piutang Dagang', 'is_expense' => false, 'type' => 'tijarah'],
            ['name' => 'Pembelian Aset Produktif Bisnis (Ternak/Tanah Dagang)', 'is_expense' => true, 'type' => 'tijarah'],
            ['name' => 'Penjualan Aset Produktif Bisnis (Ternak/Tanah Dagang)', 'is_expense' => false, 'type' => 'tijarah'],

            // Tambahan Penerimaan Modal Khusus Bisnis (Tetap aman di bawah pilar ke-3)
            ['name' => 'Modal Pengembangan / Ekspansi Usaha', 'is_expense' => false, 'type' => 'tijarah'],

            // ////////////////////////////////////////////////////////////////////////
            // INFO PERUBAHAN: [TAMBAHAN BARU - KHUSUS USAHA DAGANG / RITEL CEPAT]
            // ////////////////////////////////////////////////////////////////////////
            ['name' => 'Kulakan / Pembelian Stok Barang Dagangan', 'is_expense' => true, 'type' => 'tijarah'],
            ['name' => 'Pemasukan Penjualan Ritel / Dagang Harian', 'is_expense' => false, 'type' => 'tijarah'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
