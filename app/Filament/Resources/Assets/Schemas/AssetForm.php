<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Aset Fisik')
                    ->placeholder('Contoh: Sapi Simmental Jantan, Kebun Pohon Albasia, Emas Logam Mulia')
                    ->required(),

                Select::make('jenis')
                    ->label('Jenis Harta')
                    ->required()
                    ->live() // Memicu perubahan otomatis di layar secara real-time
                    ->options([
                        'hewan_ternak' => 'Hewan Ternak',
                        'tanah_properti' => 'Tanah & Properti',
                        'emas_logam' => 'Emas & Logam Mulia',
                        'pertanian_perkebunan' => 'Pertanian & Perkebunan (Padi/Pohon/Sawit)',
                        'lainnya' => 'Aset Fisik Lainnya',
                    ])
                    ->afterStateUpdated(function ($set, $state) {
                        // Otomatis mengunci satuan ukur agar pengguna tidak perlu mengetik manual
                        if ($state === 'hewan_ternak') $set('satuan', 'ekor');
                        if ($state === 'emas_logam') $set('satuan', 'gram');
                        if ($state === 'tanah_properti') $set('satuan', 'm2');
                        if ($state === 'pertanian_perkebunan') $set('satuan', 'kg');
                        if ($state === 'lainnya') $set('satuan', 'unit');
                    }),

                TextInput::make('sub_jenis')
                    ->label('Sub-Jenis / Spesifikasi')
                    ->placeholder('Contoh: sapi_limousin, logam_mulia_antam, pohon_albasia'),

                Select::make('gender')
                    ->label('Jenis Kelamin (Khusus Ternak)')
                    ->options([
                        'jantan' => 'Jantan',
                        'betina' => 'Betina',
                        'tidak_berlaku' => 'Tidak Berlaku (Untuk Tanah/Emas/Pohon)',
                    ])
                    ->default('tidak_berlaku'),

                Select::make('peruntukan')
                    ->label('Peruntukan (Hukum Fiqih Niat Awal)')
                    ->required()
                    ->options([
                        'qunyah' => 'Qunyah (Simpanan Pribadi / Konsumsi - Bebas Zakat)',
                        'tijarah' => 'Tijarah (Komoditas Bisnis / Investasi Aktif - Wajib Zakat 2.5%)',
                    ])
                    ->default('qunyah'),

                Toggle::make('is_saldo_awal')
                    ->label('Aset Saldo Awal')
                    ->default(false)
                    ->helperText('Aktifkan jika aset ini merupakan saldo awal (tidak memotong kas harian)'),

                DatePicker::make('tanggal_beli')
                    ->label('Tanggal Perolehan / Pembelian')
                    ->required()
                    ->default(now()),

                DatePicker::make('tanggal_jual')
                    ->label('Tanggal Pelepasan / Penjualan')
                    ->placeholder('Kosongkan jika belum laku terjual')
                    ->nullable(),

                TextInput::make('jumlah')
                    ->label('Kuantitas / Jumlah Fisik')
                    ->required()
                    ->numeric()
                    ->rules(function ($get) {
                        // JALAN TENGAH: Jika sapi/tanah wajib bulat tanpa koma, jika emas boleh pakai koma desimal
                        if (in_array($get('jenis'), ['hewan_ternak', 'tanah_properti', 'pertanian_perkebunan'])) {
                            return ['integer']; 
                        }
                        return ['numeric'];
                    })
                    ->placeholder(fn ($get) => $get('jenis') === 'emas_logam' ? 'Contoh: 10.55' : 'Contoh: 5')
                    ->default(1),

                TextInput::make('satuan')
                    ->label('Satuan Ukur')
                    ->required()
                    ->placeholder('ekor, gram, m2, kg, unit'),

                TextInput::make('persentase_milik_pribadi')
                    ->label('Porsi Kepemilikan Anda (%)')
                    ->numeric()
                    ->required()
                    ->integer()
                    ->default(100)
                    ->helperText('Isi 100 jika milik sendiri. Jika patungan/syirkah modal dengan orang lain, isi porsi persentase Anda.'),

                TextInput::make('harga_beli')
                    ->label('Harga Beli Historis (Modal Awal)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Isi 0 jika barang ini adalah hasil mutlak kelahiran di kandang / hibah gratis.'),

                TextInput::make('nilai_pasar_sekarang')
                    ->label('Nilai Pasar Saat Ini (Valuasi)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Wajib diperbarui berkala sebagai basis hisab Zakat Perniagaan Anda.'),

                Select::make('status_aset')
                    ->label('Status Operasional Harta')
                    ->required()
                    ->options([
                        'aktif' => 'Aktif (Barang hasil beli riil)',
                        'lahir_di_kandang' => 'Lahir di Kandang (Mutasi Anak Hewan - Modal Rp0)',
                        'terjual' => 'Terjual (Sudah laku & mencair jadi uang kas)',
                        'mati_rusak' => 'Mati / Rusak / Hilang (Musibah Kerugian Fisik)',
                        'dikonsumsi' => 'Dikonsumsi (Disembelih sendiri / Digunakan pribadi)',
                    ])
                    ->default('aktif'),

                TextInput::make('lokasi_keterangan')
                    ->label('Lokasi Fisik / Nomor Dokumen')
                    ->placeholder('Contoh: Kandang Blok B, Lemari Brankas Rumah, Sertifikat No. 123'),
            ]);
    }
}
