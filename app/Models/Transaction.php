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
        'asset_id',
        'asset_log_id',
        'kuantitas', // 🌟 HANYA MENAMBAHKAN INI AGAR SINKRON DENGAN STRUKTUR FLUKTUATIF BARU Anda
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
}
