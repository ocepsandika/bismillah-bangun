<?php

namespace App\Filament\Resources\Produks\Tables;

use App\Models\Produk;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProduksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_produk')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kode_sku')
                    ->label('SKU / Barcode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stok')
                    ->label('Stok')
                    ->alignEnd() // Angka stok rata kanan agar rapi
                    ->sortable()
                    ->badge() // Membuat tampilan angka stok berbentuk badge oval yang menarik
                    ->color(fn (Produk $record): string => $record->stok <= $record->batas_stok_minimum ? 'danger' : 'success'), 
                    // Otomatis berwarna MERAH jika stok kritis, dan HIJAU jika stok aman

                TextColumn::make('satuan')
                    ->label('Satuan')
                    ->sortable(),

                TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('IDR', locale: 'id_ID') // Otomatis berformat Rp 50.000
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Disembunyikan default (bisa dibuka lewat ikon mata) agar privasi modal aman dari pelanggan

                TextColumn::make('harga_jual')
                    ->label('Harga Jual')
                    ->money('IDR', locale: 'id_ID') // Otomatis berformat Rp 75.000
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
