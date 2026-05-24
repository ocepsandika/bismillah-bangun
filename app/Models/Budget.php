<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    // 1. Daftarkan kolom baru ke dalam fillable, hapus 'bulan_tahun'
    protected $fillable = [
        'category_id', 
        'start_date', 
        'end_date', 
        'plafon_anggaran'
    ];

    // 2. Wajib ditambahkan agar Laravel otomatis mengubah teks database menjadi objek Karbon/Tanggal PHP
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
