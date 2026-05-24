<?php

namespace App\Filament\Resources\Produks\Schemas;

use Filament\Schemas\Schema;
// Menggunakan Grid dari Schemas hanya untuk baris yang ingin disejajarkan
use Filament\Schemas\Components\Grid;
// Menggunakan TextInput dari Forms
use Filament\Forms\Components\TextInput;

class ProdukForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. Nama Produk (Lebar penuh, langsung mengikuti sisa ruang layar)
                TextInput::make('nama_produk')
                    ->label('Nama Produk / Barang')
                    ->placeholder('Contoh: Sabun Mandi Cair, Baju Koko Anak')
                    ->required(),

                // 2. Kode SKU / Barcode (Lebar penuh)
                TextInput::make('kode_sku')
                    ->label('Kode SKU / Barcode Unik')
                    ->placeholder('Contoh: SBN-001 (Bisa dikosongkan jika eceran)')
                    ->unique(ignoreRecord: true),

                // 3. Baris Stok & Satuan (Sejajar ke samping, memanfaatkan ruang horizontal)
                Grid::make(2)
                    ->schema([
                        TextInput::make('stok')
                            ->label('Sisa Jumlah Stok')
                            ->numeric()
                            ->integer()
                            ->default(0)
                            ->required(),

                        TextInput::make('satuan')
                            ->label('Satuan Barang')
                            ->placeholder('Contoh: Pcs, Dus, Kg')
                            ->default('Pcs')
                            ->required(),
                    ]),

                // 4. Batas Alarm Stok (Lebar penuh)
                TextInput::make('batas_stok_minimum')
                    ->label('Batas Alarm Stok Menipis')
                    ->numeric()
                    ->integer()
                    ->default(5)
                    ->required()
                    ->helperText('Sistem akan memberikan alarm tanda jika stok menyentuh angka ini atau di bawahnya.'),

                // 5. Baris Harga Beli & Harga Jual (Sejajar ke samping)
                Grid::make(2)
                    ->schema([
                        TextInput::make('harga_beli')
                            ->label('Harga Beli Kulakan (Modal Awal)')
                            ->numeric()
                            ->integer()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->helperText('Modal awal untuk menghitung keuntungan bersih kelak.'),

                        TextInput::make('harga_jual')
                            ->label('Harga Jual ke Konsumen')
                            ->numeric()
                            ->integer()
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->helperText('Harga retail final saat transaksi kasir.'),
                    ]),
            ]);
    }
}
