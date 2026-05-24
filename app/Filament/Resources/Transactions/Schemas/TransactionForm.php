<?php 

namespace App\Filament\Resources\Transactions\Schemas; 

use Filament\Forms\Components\DatePicker; 
use Filament\Forms\Components\TextInput; 
use Filament\Forms\Components\Select; 
use Filament\Schemas\Components\Grid; 
use Filament\Schemas\Schema;

class TransactionForm 
{ 
    public static function configure(Schema $schema): Schema 
    { 
        return $schema 
            ->components([ 
                // 1. Nama Transaksi (Lebar Penuh)
                TextInput::make('name') 
                    ->required() 
                    ->label('Nama Transaksi'), 

                // 2. Kategori Keuangan (Lebar Penuh)
                Select::make('category_id') 
                    ->relationship('category', 'name') 
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->is_expense 
                        ? "[Keluar] {$record->name}" 
                        : "[Masuk] {$record->name}"
                    )
                    ->searchable() 
                    ->preload() 
                    ->required() 
                    ->live() // Mengaktifkan validasi real-time untuk mendeteksi Penjualan/Kulakan
                    ->label('Kategori'), 

                // 3. Produk Ritel Master (Lebar Penuh)
                Select::make('produk_id')
                    ->label('Produk Ritel (Opsional)')
                    ->relationship('produk', 'nama_produk') 
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live() // 🌟 PENTING: Memicu form agar reaktif menyembunyikan/menampilkan kolom Kuantitas
                    ->helperText('Kosongkan jika transaksi ini adalah operasional toko biasa (cth: Bayar Listrik).'),

                // 4. Kuantitas & Nominal Uang (Berdampingan ke samping)
                Grid::make(2)
                    ->schema([
                        TextInput::make('kuantitas')
                            ->label('Jumlah Barang (Kuantitas)')
                            ->numeric()
                            ->integer()
                            ->default(1)
                            ->minValue(1)
                            
                            // 🌟 KUNCI PINTAR BARU:
                            ->hidden(fn ($get) => empty($get('produk_id'))) // Tersembunyi total dari layar jika produk_id kosong (Umum)
                            ->required(fn ($get) => !empty($get('produk_id'))) // Hanya wajib diisi jika produk_id diisi (Ritel)
                            
                            ->helperText('Isi jumlah ekor/pcs barang untuk memotong atau menambah stok otomatis.')
                            
                            // VALIDASI AMAN: Menolak transaksi jika stok habis / kurang saat jualan ritel
                            ->rules(function ($get) {
                                return [
                                    function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $produkId = $get('produk_id');
                                        $categoryId = $get('category_id');
                                        
                                        if ($produkId && $categoryId) {
                                            $produk = \App\Models\Produk::find($produkId);
                                            $category = \App\Models\Category::find($categoryId);
                                            
                                            if ($produk && $category) {
                                                $apakahIniPenjualan = !$category->is_expense;

                                                if ($apakahIniPenjualan) {
                                                    if ($produk->stok <= 0) {
                                                        $fail("Transaksi ditolak! Stok untuk produk '{$produk->nama_produk}' sudah habis (0 Pcs).");
                                                    } elseif ($value > $produk->stok) {
                                                        $fail("Transaksi ditolak! Stok '{$produk->nama_produk}' tidak mencukupi. Sisa stok hanya {$produk->stok} Pcs.");
                                                    }
                                                }
                                            }
                                        }
                                    },
                                ];
                            }),
                            
                        TextInput::make('amount') 
                            ->required() 
                            ->numeric() 
                            ->prefix('Rp') 
                            ->minValue(1)
                            ->label('Total Nominal Uang')
                            // 🌟 Mengubah teks helper secara dinamis sesuai konteks transaksi agar tidak membingungkan kasir
                            ->helperText(fn ($get) => empty($get('produk_id')) 
                                ? 'Total nilai nominal uang arus kas berjalan.' 
                                : 'Total nilai uang transaksi pasar saat ini (aman dari harga fluktuatif).'
                            ), 
                    ]),

                // 5. Tanggal Masehi (Hijriah otomatis terisi di database via Model static::saving)
                DatePicker::make('date') 
                    ->required() 
                    ->default(now())
                    ->label('Tanggal Masehi'), 

                // 6. Catatan Tambahan (Lebar Penuh)
                TextInput::make('note')  
                    ->label('Catatan'), 

                // 7. 🔒 PENGUNCI MULTI-USER: Mengunci otomatis akun Ayah/Ibu/Anak yang sedang login
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(auth()->id()) // Set otomatis ID user yang login
                    ->disabled() // Kunci kolom agar tidak bisa dimanipulasi
                    ->dehydrated() // Tetap paksa simpan nilai ID ke database saat submit
                    ->required()
                    ->label('Petugas'),
            ]); 
    } 
}
