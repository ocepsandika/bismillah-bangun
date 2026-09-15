<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockLog extends Model
{
    protected $fillable = [
        'produk_id',
        'transaction_id',
        'user_id',
        'jenis',
        'perubahan',
        'stok_sebelum',
        'stok_sesudah',
        'keterangan',
    ];

    protected $casts = [
        'perubahan' => 'integer',
        'stok_sebelum' => 'integer',
        'stok_sesudah' => 'integer',
    ];

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Penyesuaian stok manual (stok opname, barang rusak, selisih packing, dst).
     * Berbeda dari penjualan/kulakan yang lewat Transaction - ini langsung
     * menetapkan angka stok fisik yang sebenarnya, lalu menghitung sendiri
     * selisihnya untuk dicatat di log. Dipakai oleh StockLogsRelationManager.
     *
     * Dibungkus DB::transaction() + lockForUpdate(), mengikuti pola yang
     * sama persis dengan Transaction::ubahStok() supaya tidak ada race
     * condition antara penyesuaian manual dan penjualan yang berjalan
     * bersamaan.
     */
    public static function catatPenyesuaianManual(int $produkId, int $stokBaru, string $keterangan): self
    {
        if ($stokBaru < 0) {
            throw ValidationException::withMessages([
                'stok_baru' => 'Stok tidak boleh negatif.',
            ]);
        }

        return DB::transaction(function () use ($produkId, $stokBaru, $keterangan) {
            $produk = Produk::lockForUpdate()->findOrFail($produkId);

            $stokSebelum = $produk->stok;
            $perubahan = $stokBaru - $stokSebelum;

            $produk->update(['stok' => $stokBaru]);

            return static::create([
                'produk_id' => $produk->id,
                'transaction_id' => null,
                'user_id' => auth()->id(),
                'jenis' => 'penyesuaian_manual',
                'perubahan' => $perubahan,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokBaru,
                'keterangan' => $keterangan,
            ]);
        });
    }
}