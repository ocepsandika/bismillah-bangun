<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Models\Produk;
use App\Models\User;
use App\Models\Category;

class TransactionObserver
{
    /**
     * Berjalan otomatis tepat SETELAH transaksi baru berhasil disimpan ke database
     */
    public function created(Transaction $transaction): void
    {
        // === BLOK 1: OTOMATISASI NAFKAH ISTRI LAILA (KADO GAIB) ===
        if ($transaction->category_id) {
            $kategori = Category::find($transaction->category_id);
            
            if ($kategori && $kategori->name === 'Nafkah Mutlak Istri' && $transaction->is_expense) {
                // Cari akun bernama Laila di database secara otomatis
                $userIstri = User::where('name', 'LIKE', '%Laila%')->first();

                if ($userIstri) {
                    // Cari atau buatkan kategori penerimaan pendamping di sisi akun istri
                    $kategoriMasukIstri = Category::firstOrCreate(
                        ['name' => 'Penerimaan Uang Nafkah Belanja (Istri)'],
                        [
                            'is_expense' => false, // Pendapatan bagi istri
                            'type' => 'rumah_tangga'
                        ]
                    );

                    // Buatkan 1 baris transaksi pendapatan baru (Uang Masuk) secara gaib untuk Ibu Laila
                    Transaction::create([
                        'name' => 'Menerima Transfer Nafkah dari Suami',
                        'category_id' => $kategoriMasukIstri->id,
                        'user_id' => $userIstri->id, // 🔒 Kunci ke akun Ibu Laila
                        'is_expense' => false, // Status Uang Masuk bagi Ibu
                        'date' => $transaction->date,
                        'amount' => $transaction->amount, // Nominal otomatis sama persis seperti yang diinput Ayah
                        'note' => 'Otomatis tersinkronisasi dari nota nafkah Ayah. Catatan: ' . ($transaction->note ?? '-'),
                    ]);
                }
            }
        }

        // === BLOK 2: MANAJEMEN STOK PRODUK RITEL SEPERTI BIASA ===
        if ($transaction->produk_id) {
            $produk = Produk::find($transaction->produk_id);

            if ($produk) {
                // Jika umum kuantitas kosong, default ke 1 agar tidak error math
                $qty = $transaction->kuantitas ?? 1;

                if ($transaction->is_expense) {
                    $produk->increment('stok', $qty); // Kulakan = Stok bertambah
                } else {
                    $produk->decrement('stok', $qty); // Jualan = Stok berkurang
                }
            }
        }
    }
    /**
     * Berjalan otomatis jika nota transaksi dihapus (Soft Delete)
     */
    public function deleted(Transaction $transaction): void
    {
        // Mengembalikan stok jika transaksi ritel dihapus
        if ($transaction->produk_id) {
            $produk = Produk::find($transaction->produk_id);

            if ($produk) {
                $qty = $transaction->kuantitas ?? 1;

                if ($transaction->is_expense) {
                    $produk->decrement('stok', $qty); // Batalkan restock
                } else {
                    $produk->increment('stok', $qty); // Kembalikan barang ke rak
                }
            }
        }
    }

    /**
     * Berjalan otomatis jika transaksi yang dihapus dikembalikan lagi (Restore)
     */
    public function restored(Transaction $transaction): void
    {
        if ($transaction->produk_id) {
            $produk = Produk::find($transaction->produk_id);

            if ($produk) {
                $qty = $transaction->kuantitas ?? 1;

                if ($transaction->is_expense) {
                    $produk->increment('stok', $qty);
                } else {
                    $produk->decrement('stok', $qty);
                }
            }
        }
    }
}
