<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetLog extends Model 
{
    use SoftDeletes;

    protected $fillable = ['asset_id', 'tanggal', 'jenis_log', 'biaya_keluar', 'keterangan'];

    protected $casts = [
        'tanggal' => 'date',
        'biaya_keluar' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    protected static function booted()
    {
        static::created(function (self $log) {
            $log->syncAutomaticTransaction(0);
        });

        static::saved(function (self $log) {
            if (! $log->wasChanged([
                'asset_id',
                'tanggal',
                'jenis_log',
                'biaya_keluar',
                'keterangan',
            ])) {
                return;
            }

            $log->syncAutomaticTransaction((int) $log->getOriginal('biaya_keluar'));
        });

        static::deleted(function ($log) {
            if ($log->biaya_keluar > 0) {
                $asset = $log->asset;
                if ($asset) {
                    // Pengurangan harga beli saat log perawatan dihapus
                    $asset->decrement('harga_beli', $log->biaya_keluar);
                }
            }

            Transaction::where('asset_log_id', $log->id)->delete();
        });
    }

    private function syncAutomaticTransaction(int $biayaSebelumnya): void
    {
        $biayaSekarang = (int) $this->biaya_keluar;
        $asset = $this->asset;
        $tanggal = $this->tanggal ?? now();

        if ($asset) {
            $selisihBiaya = $biayaSekarang - $biayaSebelumnya;
            if ($selisihBiaya > 0) {
                $asset->increment('harga_beli', $selisihBiaya);
            } elseif ($selisihBiaya < 0) {
                $asset->decrement('harga_beli', abs($selisihBiaya));
            }
        }

        if ($biayaSekarang <= 0) {
            Transaction::where('asset_log_id', $this->id)->delete();

            return;
        }

        if ($asset) {
            $namaKategori = $asset->peruntukan === 'tijarah'
                ? 'Biaya Operasional Bisnis'
                : 'Belanja Dapur & Sembako';

            $category = Category::where('name', $namaKategori)->first();

            if ($category) {
                Transaction::updateOrCreate(
                    ['asset_log_id' => $this->id],
                    [
                        'asset_id' => $asset->id,
                        'source' => 'asset_maintenance',
                        'name' => "Perawatan [" . $asset->name . "] - " . $this->keterangan,
                        'category_id' => $category->id,
                        'is_expense' => true,
                        'date' => $tanggal,
                        'amount' => $biayaSekarang,
                    ]
                );
            }
        }
    }
}
