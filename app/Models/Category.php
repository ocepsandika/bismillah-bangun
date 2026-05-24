<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_expense',
        'type', // <-- TAMBAHKAN KOLOM BARU INI DI SINI
    ];

    /**
     * Relasi ke tabel transaksi
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    protected $casts = [
        'is_expense' => 'boolean',
    ];
}
