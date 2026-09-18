<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Models\Category;
use App\Models\Produk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label('Nama Transaksi'),

                Select::make('category_id')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query, Get $get) {
                            if (filled($get('produk_id'))) {
                                return $query->where('type', 'tijarah');
                            }
                            return $query;
                        },
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->is_expense
                        ? "[Keluar] {$record->name}"
                        : "[Masuk] {$record->name}"
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::isiOtomatisNominal($get, $set);
                    })
                    ->label('Kategori'),

                Select::make('produk_id')
                    ->label('Produk Ritel (Opsional)')
                    ->relationship('produk', 'nama_produk')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (filled($state)) {
                            $categoryId = $get('category_id');
                            if ($categoryId) {
                                $category = Category::find($categoryId);
                                if ($category && $category->type !== 'tijarah') {
                                    $set('category_id', null);
                                }
                            }
                        }
                        $set('kuantitas', 1);
                        self::isiOtomatisNominal($get, $set);
                    })
                    ->helperText('Kosongkan jika transaksi ini adalah operasional toko biasa.'),

                Grid::make(2)
                    ->schema([
                        TextInput::make('kuantitas')
                            ->label('Jumlah Barang (Kuantitas)')
                            ->numeric()
                            ->integer()
                            ->default(1)
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::isiOtomatisNominal($get, $set);
                            })
                            ->hidden(fn (Get $get) => empty($get('produk_id')))
                            ->required(fn (Get $get) => !empty($get('produk_id')))
                            ->helperText('Isi jumlah ekor/pcs barang untuk memotong atau menambah stok otomatis.'),

                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1)
                            ->live()
                            ->label('Total Nominal Uang')
                            ->helperText(fn (Get $get) => empty($get('produk_id'))
                                ? 'Total nilai nominal uang arus kas berjalan.'
                                : 'Terisi otomatis dari harga produk x kuantitas.'
                            ),
                    ]),

                DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->label('Tanggal Masehi'),

                TextInput::make('note')
                    ->label('Catatan'),

                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(auth()->id())
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->label('Petugas'),
            ]);
    }

    private static function isiOtomatisNominal(Get $get, Set $set): void
    {
        $produkId = $get('produk_id');
        $kuantitas = (int) ($get('kuantitas') ?? 0);

        if (! $produkId || $kuantitas <= 0) {
            return;
        }

        $produk = Produk::find($produkId);
        if (! $produk) {
            return;
        }

        $categoryId = $get('category_id');
        $category = $categoryId ? Category::find($categoryId) : null;

        $hargaSatuan = ($category && $category->is_expense)
            ? $produk->harga_beli
            : $produk->harga_jual;

        $set('amount', $hargaSatuan * $kuantitas);
    }
}