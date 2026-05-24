<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),

                IconColumn::make('is_expense')
                    ->label('Pengeluaran')
                    ->boolean(),

                // Tambahan kolom klasifikasi baru dengan format Badge berwarna
                TextColumn::make('type')
                    ->label('Klasifikasi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rumah_tangga' => 'Rumah Tangga',
                        'tijarah' => 'Tijarah (Bisnis)',
                        'tabarru' => 'Tabarru’ (Sosial)',
                        default => $state,
                    })
                    ->colors([
                        'info' => 'rumah_tangga',
                        'success' => 'tijarah',
                        'warning' => 'tabarru',
                    ]),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_expense')
                    ->label('Tipe Transaksi')
                    ->placeholder('Semua Tipe')
                    ->trueLabel('Hanya Pengeluaran')
                    ->falseLabel('Hanya Pemasukan'),

                // Tambahan filter opsi untuk menyaring data 3 pilar Islami
                SelectFilter::make('type')
                    ->label('Klasifikasi')
                    ->options([
                        'rumah_tangga' => 'Rumah Tangga',
                        'tijarah' => 'Tijarah (Bisnis)',
                        'tabarru' => 'Tabarru’ (Sosial)',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
