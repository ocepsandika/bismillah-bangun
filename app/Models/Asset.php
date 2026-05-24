<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model 
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'jenis', 'sub_jenis', 'gender', 'peruntukan', 
        'tanggal_beli', 'tanggal_jual', 'jumlah', 'satuan', 
        'persentase_milik_pribadi', 'harga_beli', 'nilai_pasar_sekarang', 
        'keuntungan', 'status_aset', 'lokasi_keterangan'
    ];

    protected $casts = [
        'jumlah' => 'float',
        'persentase_milik_pribadi' => 'integer',
        'harga_beli' => 'integer',
        'nilai_pasar_sekarang' => 'integer',
        'keuntungan' => 'integer',
        'tanggal_beli' => 'date',
        'tanggal_jual' => 'date',
    ];

    // Hubungan relasi ke catatan log perawatan berkala
    public function logs()
    {
        return $this->hasMany(AssetLog::class, 'asset_id');
    }

    protected static function booted()
    {
        // Hitung keuntungan sebelum data disimpan
        static::saving(function ($asset) {
            $asset->keuntungan = $asset->nilai_pasar_sekarang - $asset->harga_beli;
        });

        static::saved(function ($asset) {
            // Skenario Aset Aktif Hasil Beli Baru ATAU Registrasi Harta Lama
            if ($asset->status_aset === 'aktif') {
                
                // --- INTEGRASI PENGAMAN DETEKSI KATA KUNCI ASET LAMA ---
                $isAsetLama = preg_match('/(lama|awal|historis)/i', $asset->name);

                if ($isAsetLama) {
                    // JIKA ASET LAMA: Alihkan ke kategori Saldo Awal agar 'is_expense' bernilai FALSE (Modal tidak memotong kas harian)
                    $namaKategori = $asset->peruntukan === 'tijarah'
                        ? 'Saldo Awal Aset Produktif Bisnis Historis'
                        : 'Saldo Awal Aset Simpanan Pribadi Historis';

                    $category = Category::firstOrCreate(
                        ['name' => $namaKategori],
                        [
                            'is_expense' => false, // FALSE = Tidak memotong saldo kas harian Anda saat ini
                            'type' => $asset->peruntukan === 'tijarah' ? 'tijarah' : 'rumah_tangga'
                        ]
                    );

                    if ($category) {
                        Transaction::updateOrCreate(
                            ['note' => "Otomatis: Pembelian Aset ID-{$asset->id}"],
                            [
                                'name' => "Saldo Awal: " . $asset->name,
                                'category_id' => $category->id,
                                'is_expense' => false,
                                'date' => $asset->tanggal_beli,
                                'amount' => $asset->harga_beli,
                            ]
                        );
                    }
                } else {
                    // JIKA ASET BARU (KODE ASLI ANDA): Berjalan normal memotong uang kas harian ('is_expense' => true)
                    $namaKategori = $asset->peruntukan === 'tijarah' 
                        ? 'Pembelian Aset Produktif Bisnis (Ternak/Tanah Dagang)' 
                        : 'Pembelian Aset Simpanan Pribadi (Emas/Tanah/Ternak)';

                    $category = Category::where('name', $namaKategori)->first();

                    if ($category) {
                        Transaction::updateOrCreate(
                            ['note' => "Otomatis: Pembelian Aset ID-{$asset->id}"],
                            [
                                'name' => "Beli " . $asset->name,
                                'category_id' => $category->id,
                                'is_expense' => true,
                                'date' => $asset->tanggal_beli,
                                'amount' => $asset->harga_beli,
                            ]
                        );
                    }
                }
            }

            if ($asset->status_aset === 'lahir_di_kandang') {
                Transaction::where('note', "Otomatis: Pembelian Aset ID-{$asset->id}")->delete();
            }

            // Skenario Aset Dijual -> Tambah kas tunai mengikuti porsi kepemilikan pribadi
            if ($asset->status_aset === 'terjual') {
                $namaKategori = $asset->peruntukan === 'tijarah' 
                    ? 'Penjualan Aset Produktif Bisnis (Ternak/Tanah Dagang)' 
                    : 'Penjualan Aset Simpanan Pribadi (Emas/Tanah/Ternak)';

                $category = Category::where('name', $namaKategori)->first();

                if ($category) {
                    $uangMasukRiil = ($asset->nilai_pasar_sekarang * $asset->persentase_milik_pribadi) / 100;

                    Transaction::updateOrCreate(
                        ['note' => "Otomatis: Penjualan Aset ID-{$asset->id}"],
                        [
                            'name' => "Jual " . $asset->name,
                            'category_id' => $category->id,
                            'is_expense' => false,
                            'date' => $asset->tanggal_jual ?? now(),
                            'amount' => $uangMasukRiil,
                        ]
                    );
                }
            }

            if (in_array($asset->status_aset, ['mati_rusak', 'dikonsumsi'])) {
                Transaction::where('note', "Otomatis: Penjualan Aset ID-{$asset->id}")->delete();
            }
        });

        static::deleted(function ($asset) {
            Transaction::where('note', "Otomatis: Pembelian Aset ID-{$asset->id}")->delete();
            Transaction::where('note', "Otomatis: Penjualan Aset ID-{$asset->id}")->delete();
        });
    }
}
