<?php

namespace App\Filament\Resources\Budgets\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class BudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // SUDAH DIPERBAIKI: Menghapus parameter nama locale: 'id' yang memicu eror
                TextColumn::make('start_date')
                    ->label('Periode Anggaran')
                    ->date('d M Y') 
                    ->description(fn ($record) => 's/d ' . $record->end_date?->format('d M Y'))
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('plafon_anggaran')
                    ->label('Plafon Anggaran')
                    ->numeric(decimalPlaces: 0, locale: 'id') // Parameter locale di sini tetap aman karena ini fungsi numeric, bukan date
                    ->prefix('Rp ')
                    ->sortable(),
            ])
            ->filters([
                // Filter pencarian berdasarkan rentang kalender
                Filter::make('rentang_periode')
                    ->form([
                        DatePicker::make('dari_tanggal')->label('Dari Tanggal')->native(false),
                        DatePicker::make('sampai_tanggal')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    })
            ]);
    }
}
