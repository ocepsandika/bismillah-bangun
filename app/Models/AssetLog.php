<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetLog extends Model 
{
    protected $fillable = ['asset_id', 'tanggal', 'jenis_log', 'biaya_keluar', 'keterangan'];

    protected $casts = [
        'tanggal' => 'date',
        'biaya_keluar' => 'integer',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    protected static function booted()
    {
        static::saved(function ($log) {
            if ($log->biaya_keluar > 0) {
                $asset = $log->asset;
                if ($asset) {
                    
                    // --- PENGAMAN EDIT LOG: KALKULASI SINKRONISASI MODAL HISTORIS ---
                    if ($log->wasRecentlyCreated) {
                        // Jika log baru dibuat, langsung tambah harga beli secara penuh
                        $asset->increment('harga_beli', $log->biaya_keluar);
                    } else {
                        // Jika log lama diedit, hitung selisih biaya baru dengan biaya lama agar modal tetap akurat
                        $selisihBiaya = $log->biaya_keluar - $log->getOriginal('biaya_keluar');
                        if ($selisihBiaya != 0) {
                            $asset->increment('harga_beli', $selisihBiaya);
                        }
                    }

                    // Penentuan nama kategori syariah sesuai pilar peruntukan Anda
                    $namaKategori = $asset->peruntukan === 'tijarah' 
                        ? 'Biaya Operasional Bisnis' 
                        : 'Belanja Dapur & Sembako';

                    $category = Category::where('name', $namaKategori)->first();

                    if ($category) {
                        Transaction::updateOrCreate(
                            ['note' => "Otomatis: Biaya Perawatan Log ID-{$log->id}"],
                            [
                                'name' => "Perawatan [" . $asset->name . "] - " . $log->keterangan,
                                'category_id' => $category->id,
                                'is_expense' => true, // Selaku biaya perawatan kas berjalan harian
                                'date' => $log->tanggal,
                                'amount' => $log->biaya_keluar,
                            ]
                        );
                    }
                }
            }
        });

        static::deleted(function ($log) {
            if ($log->biaya_keluar > 0) {
                $asset = $log->asset;
                if ($asset) {
                    // Pengurangan harga beli saat log perawatan dihapus
                    $asset->decrement('harga_beli', $log->biaya_keluar);
                }
                Transaction::where('note', "Otomatis: Biaya Perawatan Log ID-{$log->id}")->delete();
            }
        });
    }
}
