<?php

namespace App\Filament\Resources\Assets\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\Summarizers\Sum; // Library resmi penghitung total otomatis di bawah tabel

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_saldo_awal')
                    ->label('Saldo Awal')
                    ->boolean()
                    ->tooltip('Aset saldo awal tidak memotong kas harian'),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hewan_ternak' => 'warning',
                        'tanah_properti' => 'info',
                        'emas_logam' => 'success',
                        'pertanian_perkebunan' => 'primary',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('peruntukan')
                    ->label('Status Fiqih')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'tijarah' ? 'danger' : 'success')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'tijarah' => 'Wajib Zakat (Tijarah)',
                        'qunyah' => 'Simpanan (Qunyah)',
                        default => $state
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($record) => "{$record->jumlah} {$record->satuan}"),

                // MODAL AWAL BULAT TANPA KOMA ,00
                TextColumn::make('harga_beli')
                    ->label('Modal Awal')
                    ->numeric(decimalPlaces: 0, locale: 'id')
                    ->sortable()
                    ->summarize(Sum::make()->numeric(decimalPlaces: 0, locale: 'id')->prefix('Rp ')->label('Total Modal')),

                // NILAI PASAR BULAT TANPA KOMA ,00
                TextColumn::make('nilai_pasar_sekarang')
                    ->label('Nilai Pasar')
                    ->numeric(decimalPlaces: 0, locale: 'id')
                    ->sortable()
                    ->summarize(Sum::make()->numeric(decimalPlaces: 0, locale: 'id')->prefix('Rp ')->label('Total Valuasi')),

                // 🔥 BADGE KESEGARAN VALUASI — indikator umur data nilai pasar
                TextColumn::make('valuation_age')
                    ->label('Kesegaran Valuasi')
                    ->badge()
                    ->state(fn ($record) => match ($record->valuation_freshness) {
                        'fresh'   => "✅ {$record->valuation_age_days} hari lalu",
                        'warning' => "⚠️ {$record->valuation_age_days} hari lalu",
                        'stale'   => "🔴 {$record->valuation_age_days} hari lalu",
                        'unknown' => '⚪ Belum pernah',
                    })
                    ->color(fn ($record): string => match ($record->valuation_freshness) {
                        'fresh'   => 'success',
                        'warning' => 'warning',
                        'stale'   => 'danger',
                        default   => 'gray',
                    })
                    ->tooltip(fn ($record) => $record->nilai_pasar_updated_at
                        ? 'Terakhir diperbarui: ' . $record->nilai_pasar_updated_at->format('d-m-Y H:i')
                        : 'Nilai pasar belum pernah diperbarui'
                    )
                    ->sortable(),

                // PERBAIKAN UTAMA: Menghitung profit secara real-time & total summary yang lolos validasi framework
                TextColumn::make('keuntungan')
                    ->label('Profit / Kerugian')
                    ->sortable()
                    ->state(fn ($record) => $record->nilai_pasar_sekarang - $record->harga_beli)
                    ->color(fn ($state): string => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state >= 0 ? '+ Rp ' . number_format($state, 0, ',', '.') : '- Rp ' . number_format(abs($state), 0, ',', '.'))
                    ->summarize(
                        Sum::make()
                            ->label('Total Laba Bersih')
                            ->formatStateUsing(function ($state, Table $table) {
                                $records = $table->getRecords();
                                $totalProfit = $records->sum(fn ($r) => $r->nilai_pasar_sekarang - $r->harga_beli);
                                return $totalProfit >= 0 ? '+ Rp ' . number_format($totalProfit, 0, ',', '.') : '- Rp ' . number_format(abs($totalProfit), 0, ',', '.');
                            })
                    ),

                TextColumn::make('status_aset')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'lahir_di_kandang' => 'info',
                        'terjual' => 'gray',
                        'mati_rusak' => 'danger',
                        'dikonsumsi' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('tanggal_beli')
                    ->label('Tanggal Perolehan')
                    ->date('d-m-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Timestamp mentah — info detail (toggleable, sembunyi default)
                TextColumn::make('nilai_pasar_updated_at')
                    ->label('Tgl Update Harga')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Saring Jenis Aset')
                    ->options([
                        'hewan_ternak' => 'Hewan Ternak',
                        'tanah_properti' => 'Tanah & Properti',
                        'emas_logam' => 'Emas & Logam Mulia',
                        'pertanian_perkebunan' => 'Pertanian & Perkebunan',
                    ]),
                SelectFilter::make('peruntukan')
                    ->label('Saring Hukum Zakat')
                    ->options([
                        'qunyah' => 'Qunyah (Simpanan)',
                        'tijarah' => 'Tijarah (Bisnis)',
                    ]),
                SelectFilter::make('status_aset')
                    ->label('Saring Status Fisik')
                    ->options([
                        'aktif' => 'Aktif',
                        'lahir_di_kandang' => 'Lahir di Kandang',
                        'terjual' => 'Terjual',
                        'mati_rusak' => 'Mati / Rusak',
                        'dikonsumsi' => 'Dikonsumsi',
                    ]),

                TernaryFilter::make('is_saldo_awal')
                    ->label('Saldo Awal')
                    ->placeholder('Semua Aset')
                    ->trueLabel('Hanya Saldo Awal')
                    ->falseLabel('Bukan Saldo Awal'),
            ]);
    }
}