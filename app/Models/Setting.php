<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'harga_emas_per_gram',
    ];
 
    protected $casts = [
        'harga_emas_per_gram' => 'integer',
    ];
 
    /**
     * Aplikasi ini hanya butuh satu baris pengaturan (singleton).
     * firstOrCreate memastikan baris itu selalu ada, dengan nilai default
     * yang sama seperti angka hardcode lama di RingkasanSaldoSyariah -
     * jadi tidak ada perubahan perilaku sebelum admin mengubahnya lewat
     * halaman Pengaturan Syariah.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'harga_emas_per_gram' => 1_450_000,
        ]);
    }
}
