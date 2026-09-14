<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model 
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'is_saldo_awal', 'jenis', 'sub_jenis', 'gender', 'peruntukan',
        'tanggal_beli', 'tanggal_jual', 'jumlah', 'satuan', 
        'persentase_milik_pribadi', 'harga_beli', 'nilai_pasar_sekarang', 
        'keuntungan', 'status_aset', 'lokasi_keterangan'
    ];

    protected $casts = [
        'is_saldo_awal' => 'boolean',
        'jumlah' => 'float',
        'persentase_milik_pribadi' => 'integer',
        'harga_beli' => 'integer',
        'nilai_pasar_sekarang' => 'integer',
        'keuntungan' => 'integer',
        'tanggal_beli' => 'date',
        'tanggal_jual' => 'date',
    ];

    // Hubungan relasi ke catatan log perawatan berkala
    public function logs(): HasMany
    {
        return $this->hasMany(AssetLog::class, 'asset_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function booted()
    {
        // Hitung keuntungan sebelum data disimpan
        static::saving(function ($asset) {
            $asset->keuntungan = $asset->nilai_pasar_sekarang - $asset->harga_beli;
        });

        static::created(function (self $asset) {
            $asset->syncAutomaticTransactions();
        });

        static::saved(function (self $asset) {
            if (! $asset->wasChanged([
                'name',
                'is_saldo_awal',
                'peruntukan',
                'tanggal_beli',
                'tanggal_jual',
                'harga_beli',
                'nilai_pasar_sekarang',
                'persentase_milik_pribadi',
                'status_aset',
            ])) {
                return;
            }

            $asset->syncAutomaticTransactions();
        });

        static::deleted(function ($asset) {
            Transaction::where('asset_id', $asset->id)
                ->whereIn('source', ['asset_buy', 'asset_sell'])
                ->delete();
        });
    }

    private function syncAutomaticTransactions(): void
    {
        $statusAset = $this->status_aset ?? 'aktif';
        $tanggalBeli = $this->tanggal_beli ?? now();

        if ($statusAset === 'aktif') {
            if ($this->is_saldo_awal) {
                $namaKategori = $this->peruntukan === 'tijarah'
                    ? 'Saldo Awal Aset Produktif Bisnis Historis'
                    : 'Saldo Awal Aset Simpanan Pribadi Historis';

                $category = Category::firstOrCreate(
                    ['name' => $namaKategori],
                    [
                        'is_expense' => false, // FALSE = Tidak memotong saldo kas harian Anda saat ini
                        'type' => $this->peruntukan === 'tijarah' ? 'tijarah' : 'rumah_tangga',
                    ]
                );

                if ($category) {
                    Transaction::updateOrCreate(
                        ['asset_id' => $this->id, 'source' => 'asset_buy'],
                        [
                            'name' => "Saldo Awal: " . $this->name,
                            'category_id' => $category->id,
                            'is_expense' => false,
                            'date' => $tanggalBeli,
                            'amount' => $this->harga_beli,
                        ]
                    );
                }
            } else {
                $namaKategori = $this->peruntukan === 'tijarah'
                    ? 'Pembelian Aset Produktif Bisnis (Ternak/Tanah Dagang)'
                    : 'Pembelian Aset Simpanan Pribadi (Emas/Tanah/Ternak)';

                $category = Category::where('name', $namaKategori)->first();

                if ($category) {
                    Transaction::updateOrCreate(
                        ['asset_id' => $this->id, 'source' => 'asset_buy'],
                        [
                            'name' => "Beli " . $this->name,
                            'category_id' => $category->id,
                            'is_expense' => true,
                            'date' => $tanggalBeli,
                            'amount' => $this->harga_beli,
                        ]
                    );
                }
            }
        }

        if ($statusAset === 'lahir_di_kandang') {
            Transaction::where('asset_id', $this->id)->where('source', 'asset_buy')->delete();
        }

        // Skenario Aset Dijual -> Tambah kas tunai mengikuti porsi kepemilikan pribadi
        if ($statusAset === 'terjual') {
            $namaKategori = $this->peruntukan === 'tijarah'
                ? 'Penjualan Aset Produktif Bisnis (Ternak/Tanah Dagang)'
                : 'Penjualan Aset Simpanan Pribadi (Emas/Tanah/Ternak)';

            $category = Category::where('name', $namaKategori)->first();

            if ($category) {
                $uangMasukRiil = ($this->nilai_pasar_sekarang * $this->persentase_milik_pribadi) / 100;

                Transaction::updateOrCreate(
                    ['asset_id' => $this->id, 'source' => 'asset_sell'],
                    [
                        'name' => "Jual " . $this->name,
                        'category_id' => $category->id,
                        'is_expense' => false,
                        'date' => $this->tanggal_jual ?? now(),
                        'amount' => $uangMasukRiil,
                    ]
                );
            }
        }

        if (in_array($statusAset, ['mati_rusak', 'dikonsumsi'], true)) {
            Transaction::where('asset_id', $this->id)->where('source', 'asset_sell')->delete();
        }
    }
}
