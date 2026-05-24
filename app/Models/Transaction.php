<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use IntlDateFormatter;
use DateTime;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category_id',
        'user_id',    
        'produk_id',  
        'kuantitas', // 🌟 HANYA MENAMBAHKAN INI AGAR SINKRON DENGAN STRUKTUR FLUKTUATIF BARU Anda
        'is_expense',
        'date',
        'date_hijri',
        'month_hijri',
        'year_hijri',
        'amount',
        'note'
    ];

    protected $casts = [
        'is_expense' => 'boolean',
        'date' => 'date',
        'amount' => 'integer',
    ];

    /**
     * 🔒 TETAP DIPERTAHANKAN (100% ASLI MILIK ANDA): Otomatisasi Kalender Hijriah Tanpa Perubahan
     */
    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->isDirty('date') && $model->date) {
                $dateString = $model->date instanceof Carbon ? $model->date->toDateString() : Carbon::parse($model->date)->toDateString();
                $date = new DateTime($dateString);

                $textFormatter = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Jakarta', IntlDateFormatter::TRADITIONAL, "d MMMM yyyy 'H'");
                $monthFormatter = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Jakarta', IntlDateFormatter::TRADITIONAL, "M");
                $yearFormatter = new IntlDateFormatter('id_ID@calendar=islamic-umalqura', IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Asia/Jakarta', IntlDateFormatter::TRADITIONAL, "yyyy");

                $model->date_hijri = $textFormatter->format($date);
                $model->month_hijri = (int) $monthFormatter->format($date);
                $model->year_hijri = (int) $yearFormatter->format($date);
            }
        });
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
}
