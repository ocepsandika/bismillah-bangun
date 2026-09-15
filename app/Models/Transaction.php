<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use IntlDateFormatter;
use DateTime;

class Transaction extends Model
{
    // 'restore' didefinisikan oleh trait SoftDeletes, bukan oleh parent class,
    // jadi tidak bisa dipanggil lewat parent::restore(). Alias di sini supaya
    // kita tetap bisa memanggil implementasi asli trait dari dalam override kita.
    use SoftDeletes {
        restore as private baseRestore;
    }

    protected $fillable = [
        'name',
        'category_id',
        'user_id',
        'produk_id',
        'asset_id',
        'asset_log_id',
        'kuantitas', // ðŸŒŸ HANYA MENAMBAHKAN INI AGAR SINKRON DENGAN STRUKTUR FLUKTUATIF BARU Anda
        'is_expense',
        'date',
        'amount',
        'note',
        'source',
    ];

    protected $casts = [
        'is_expense' => 'boolean',
        'asset_id' => 'integer',
        'asset_log_id' => 'integer',
        'produk_id' => 'integer',
        'kuantitas' => 'integer',
        'date' => 'date',
        'amount' => 'integer',
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (! $model->isDirty('date')) {
                return;
            }

            if (! $model->date || ! class_exists(IntlDateFormatter::class)) {
                $model->clearHijriDate();

                return;
            }

            try {
                $dateString = $model->date instanceof Carbon
                    ? $model->date->toDateString()
                    : Carbon::parse($model->date)->toDateString();
                $date = new DateTime($dateString);

                $textFormatter = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Jakarta', IntlDateFormatter::TRADITIONAL, "d MMMM yyyy 'H'");
                $monthFormatter = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Jakarta', IntlDateFormatter::TRADITIONAL, "M");
                $yearFormatter = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Jakarta', IntlDateFormatter::TRADITIONAL, "yyyy");

                $dateHijri = $textFormatter->format($date);
                $monthHijri = $monthFormatter->format($date);
                $yearHijri = $yearFormatter->format($date);

                if (! is_string($dateHijri) || ! is_numeric($monthHijri) || ! is_numeric($yearHijri)) {
                    $model->clearHijriDate();

                    return;
                }

                $model->date_hijri = $dateHijri;
                $model->month_hijri = (int) $monthHijri;
                $model->year_hijri = (int) $yearHijri;
            } catch (\Throwable) {
                $model->clearHijriDate();
            }
        });

        // === SINKRONISASI STOK PRODUK RITEL ===
        // Pola mengikuti AssetLog: hitung delta antara nilai lama & baru,
        // lalu naik/turunkan stok - bukan menimpa angka stok langsung.

        static::creating(function (self $transaction) {
            $transaction->validasiStokRitel();
        });

        static::created(function (self $transaction) {
            $transaction->sinkronStokProduk(
                produkIdLama: null,
                kuantitasLama: 0,
                isExpenseLama: null,
            );
        });

        static::updating(function (self $transaction) {
            if ($transaction->isDirty(['produk_id', 'kuantitas', 'is_expense'])) {
                $transaction->validasiStokRitel();
            }
        });

        static::updated(function (self $transaction) {
            if (! $transaction->wasChanged(['produk_id', 'kuantitas', 'is_expense'])) {
                return;
            }

            $transaction->sinkronStokProduk(
                produkIdLama: $transaction->getOriginal('produk_id'),
                kuantitasLama: (int) $transaction->getOriginal('kuantitas'),
                isExpenseLama: (bool) $transaction->getOriginal('is_expense'),
            );
        });
    }

    private function clearHijriDate(): void
    {
        $this->date_hijri = null;
        $this->month_hijri = null;
        $this->year_hijri = null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function assetLog(): BelongsTo
    {
        return $this->belongsTo(AssetLog::class);
    }

    /**
     * FIX BUG 1
     * ---------
     * save() dibungkus DB::transaction() secara menyeluruh, bukan hanya
     * validasiStokRitel() saja. Alasannya: MySQL meng-commit setiap statement
     * begitu selesai selama tidak ada transaksi eksplisit yang sedang terbuka.
     * Kalau kita hanya membungkus validasiStokRitel() di dalam creating(),
     * transaksi kecil itu langsung commit (lock dilepas) begitu closure-nya
     * selesai - PADAHAL proses INSERT baru terjadi setelahnya. Antara saat
     * lock dilepas dan saat INSERT benar-benar berjalan, request lain masih
     * bisa menyelip.
     *
     * Dengan membungkus seluruh save() (creating -> INSERT/UPDATE -> created),
     * lock dari Produk::lockForUpdate() yang diambil di validasiStokRitel()
     * tetap dipegang sampai seluruh proses (termasuk pengurangan stok di
     * ubahStok()) selesai dan transaksi benar-benar commit. Kalau ada
     * kegagalan di titik mana pun (termasuk saat ubahStok() menemukan stok
     * ternyata sudah habis oleh transaksi lain), SEMUANYA di-rollback -
     * termasuk baris Transaction yang baru saja "berhasil" di-insert.
     */
    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    /**
     * delete() (soft delete) tidak lewat save(), jadi dibungkus transaksi
     * sendiri supaya pembalikan stok atomik dengan proses soft delete-nya.
     */
    public function delete()
    {
        return DB::transaction(function () {
            $produkId = $this->produk_id;
            $kuantitas = (int) $this->kuantitas;
            $isExpense = (bool) $this->is_expense;

            $deleted = parent::delete();

            if ($deleted && $produkId && $kuantitas > 0) {
                $this->ubahStok($produkId, $kuantitas, $isExpense, balik: true, jenis: 'dihapus');
            }

            return $deleted;
        });
    }

    /**
     * forceDelete() menghapus baris secara permanen dan TIDAK melewati
     * delete() di atas, jadi ditangani terpisah supaya stok tetap konsisten
     * kalau baris yang masih "hidup" efeknya (belum di-soft-delete) langsung
     * dihapus permanen.
     *
     * Catatan: saat ini TransactionsTable belum menyediakan tombol force
     * delete di UI (tidak ada ForceDeleteBulkAction/TrashedFilter seperti di
     * ProduksTable) - jadi jalur ini baru bisa dipicu manual (Tinker, job,
     * command). Override ini bersifat jaga-jaga, bukan untuk tombol yang
     * sudah ada. Kalau nanti Anda menambahkan ForceDeleteBulkAction ke
     * TransactionsTable, method ini otomatis akan menangani reverse stoknya.
     */
    public function forceDelete()
    {
        return DB::transaction(function () {
            $produkId = $this->produk_id;
            $kuantitas = (int) $this->kuantitas;
            $isExpense = (bool) $this->is_expense;
            $sudahTerhapusLembut = $this->trashed();

            $deleted = parent::forceDelete();

            // Kalau baris sebelumnya sudah soft-deleted, efek stoknya sudah
            // dibalik saat delete() dipanggil dulu - jangan dibalik dua kali.
            if ($deleted && ! $sudahTerhapusLembut && $produkId && $kuantitas > 0) {
                $this->ubahStok($produkId, $kuantitas, $isExpense, balik: true, jenis: 'dihapus_permanen');
            }

            return $deleted;
        });
    }

    /**
     * FIX BUG 2
     * ---------
     * 'restore' berasal dari trait SoftDeletes (bukan parent class), maka
     * dipanggil lewat alias baseRestore() (lihat 'use SoftDeletes { ... }'
     * di atas), bukan parent::restore().
     *
     * Validasi stok dilakukan SEBELUM baris benar-benar dipulihkan, di
     * dalam kunci baris (lockForUpdate) & transaksi yang sama dengan proses
     * restore + penambahan/pengurangan stoknya. Kalau stok tidak cukup,
     * ValidationException dilempar SEBELUM baseRestore() dipanggil, jadi
     * baris tetap soft-deleted (restore benar-benar ditolak, bukan cuma
     * ditolak "sebagian" setelah deleted_at sudah terlanjur di-null-kan).
     */
    public function restore()
    {
        return DB::transaction(function () {
            $produkId = $this->produk_id;
            $kuantitas = (int) $this->kuantitas;
            $isExpense = (bool) $this->is_expense;

            // Hanya transaksi penjualan ritel (is_expense = false) yang
            // MENGURANGI stok saat dipulihkan - itu satu-satunya arah yang
            // berisiko bikin stok minus, jadi itu yang perlu dicek dulu.
            if ($produkId && $kuantitas > 0 && ! $isExpense) {
                $produk = Produk::lockForUpdate()->find($produkId);

                if (! $produk || $produk->stok < $kuantitas) {
                    throw ValidationException::withMessages([
                        'kuantitas' => "Transaksi tidak bisa dipulihkan: stok produk '"
                            . ($produk->nama_produk ?? '-')
                            . "' tidak mencukupi (sisa: " . ($produk->stok ?? 0) . ").",
                    ]);
                }
            }

            $restored = $this->baseRestore();

            if ($restored && $produkId && $kuantitas > 0) {
                $this->ubahStok($produkId, $kuantitas, $isExpense, balik: false, jenis: 'dipulihkan');
            }

            return $restored;
        });
    }

    /**
     * Validasi awal yang ramah pengguna (early check), dipanggil sebelum
     * baris disimpan. Ini PELENGKAP validasi form Filament yang sudah ada
     * di ProdukForm, bukan penggantinya - form tetap dipakai untuk UX
     * (pesan error instan tanpa perlu submit). Cek yang benar-benar
     * mencegah race condition ada di ubahStok(), bukan di sini.
     */
    private function validasiStokRitel(): void
    {
        if (! $this->produk_id || $this->is_expense || (int) $this->kuantitas <= 0) {
            // hanya penjualan (is_expense = false) yang mengurangi stok
            return;
        }

        $produk = Produk::lockForUpdate()->find($this->produk_id);

        if (! $produk || $produk->stok < (int) $this->kuantitas) {
            throw ValidationException::withMessages([
                'kuantitas' => "Stok produk '" . ($produk->nama_produk ?? '-') . "' tidak mencukupi (sisa: " . ($produk->stok ?? 0) . ").",
            ]);
        }
    }

    /**
     * Membalik efek stok dari data lama (kalau ada), lalu menerapkan efek
     * stok dari data baru. Dipanggil dari dalam creating/created/updating/
     * updated, yang semuanya sudah berjalan di dalam transaksi yang dibuka
     * oleh save() override di atas - jadi DB::transaction() di sini menjadi
     * savepoint bersarang (aman, bukan masalah).
     */
    private function sinkronStokProduk(?int $produkIdLama, int $kuantitasLama, ?bool $isExpenseLama): void
    {
        DB::transaction(function () use ($produkIdLama, $kuantitasLama, $isExpenseLama) {
            // Kalau ini reversal dari data lama pada UPDATE (produkIdLama terisi),
            // catat sebagai 'koreksi_edit'. Kalau ini penerapan awal saat CREATE
            // (produkIdLama null), catat sesuai arah aslinya (kulakan/penjualan).
            if ($produkIdLama && $kuantitasLama > 0) {
                $this->ubahStok($produkIdLama, $kuantitasLama, (bool) $isExpenseLama, balik: true, jenis: 'koreksi_edit');
            }

            if ($this->produk_id && (int) $this->kuantitas > 0) {
                $jenisBaru = $produkIdLama ? 'koreksi_edit' : ((bool) $this->is_expense ? 'kulakan' : 'penjualan');
                $this->ubahStok($this->produk_id, (int) $this->kuantitas, (bool) $this->is_expense, balik: false, jenis: $jenisBaru);
            }
        });
    }

    /**
     * Satu-satunya tempat yang benar-benar mengubah angka stok.
     *
     * Arah normal:
     *   is_expense = true  (kulakan/restock) -> stok bertambah
     *   is_expense = false (penjualan ritel)  -> stok berkurang
     * Saat $balik = true (reverse: update lama/delete/undo), arah dibalik.
     *
     * Pemeriksaan stok di sini (bukan hanya di validasiStokRitel()) adalah
     * yang BENAR-BENAR mencegah race condition: lockForUpdate() di sini
     * dan penambahan/pengurangannya berada di baris kode yang sama, di
     * dalam transaksi yang sama, sehingga tidak ada celah antara "cek" dan
     * "ubah" seperti yang terjadi kalau pengecekan hanya di creating().
     *
     * Setiap kali stok benar-benar berubah di sini, satu baris StockLog
     * ikut tercatat pada transaksi database yang sama - jadi log tidak
     * pernah "ketinggalan" dari angka stok yang sebenarnya (kalau salah
     * satu di-rollback, yang lain ikut di-rollback).
     */
    private function ubahStok(int $produkId, int $kuantitas, bool $isExpense, bool $balik, string $jenis): void
    {
        if ($kuantitas <= 0) {
            return;
        }

        $produk = Produk::lockForUpdate()->find($produkId);

        if (! $produk) {
            return;
        }

        $tambahStok = $isExpense; // true = kulakan, stok bertambah
        if ($balik) {
            $tambahStok = ! $tambahStok;
        }

        if (! $tambahStok && $produk->stok < $kuantitas) {
            throw ValidationException::withMessages([
                'kuantitas' => "Stok produk '{$produk->nama_produk}' tidak mencukupi saat transaksi disimpan (sisa: {$produk->stok}).",
            ]);
        }

        $stokSebelum = $produk->stok;
        $perubahan = $tambahStok ? $kuantitas : -$kuantitas;

        $tambahStok
            ? $produk->increment('stok', $kuantitas)
            : $produk->decrement('stok', $kuantitas);

        StockLog::create([
            'produk_id' => $produk->id,
            // $this->exists baru true setelah INSERT (creating -> belum ada id).
            // Aman dipakai di sini karena ubahStok() hanya dipanggil dari
            // created/updated/delete/forceDelete/restore - semuanya setelah
            // baris Transaction benar-benar punya id.
            'transaction_id' => $this->exists ? $this->id : null,
            'user_id' => $this->user_id ?? auth()->id(),
            'jenis' => $jenis,
            'perubahan' => $perubahan,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSebelum + $perubahan,
        ]);
    }
}