<?php

namespace App\Filament\Resources\Transactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->label('Nama'),

                IconColumn::make('is_expense')
                    ->boolean()
                    ->sortable()
                    ->label('Pengeluaran'),

                TextColumn::make('category.name')
                    ->sortable()
                    ->label('Kategori'),

                // 🌟 MENAMPILKAN PRODUK RITEL YANG TERLIBAT
                TextColumn::make('produk.nama_produk')
                    ->label('Produk Ritel')
                    ->default('---') // Menampilkan tanda strip jika berupa pengeluaran operasional umum
                    ->searchable(),

                // 🌟 MENAMPILKAN JUMLAH BARANG YANG DIINPUT KASIR
                TextColumn::make('kuantitas')
                    ->label('Qty')
                    ->alignCenter() // Angka ditaruh di tengah agar rapi bersanding dengan nominal rupiah
                    ->sortable(),

                TextColumn::make('date')
                    ->date('d-m-Y')
                    ->sortable()
                    ->label('Tanggal Masehi'),

                TextColumn::make('date_hijri')
                    ->label('Tanggal Hijriah')
                    ->searchable()
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('year_hijri', $direction)
                                     ->orderBy('month_hijri', $direction)
                                     ->orderBy('date', $direction);
                    }),

                TextColumn::make('amount')
                    ->money('IDR', locale: 'id_ID')
                    ->color(fn ($record) => $record->is_expense ? 'danger' : 'success')
                    ->sortable()
                    ->label('Nominal'),

                TextColumn::make('note')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Catatan'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                TernaryFilter::make('is_expense')
                    ->label('Tipe Transaksi')
                    ->placeholder('Semua Transaksi')
                    ->trueLabel('Hanya Pengeluaran')
                    ->falseLabel('Hanya Pemasukan'),

                SelectFilter::make('category_id')
                    ->label('Saring Kategori')
                    ->relationship('category', 'name'),
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
