<?php

namespace App\Filament\Pages;

use App\Models\Transaction;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput; 
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid; // --- MEMAKAI GRID BERBASIS SCHEMA AGAR SERAGAM ---
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\RingkasanSaldoSyariah::class,
        ];
    }

public function filtersForm(Schema $schema): Schema
{
    // Fungsi bantu untuk mengubah Tanggal Masehi (Y-m-d) menjadi String Hijriah secara akurat
    $konversiKeHijriah = function ($tanggalMasehi) {
        if (! $tanggalMasehi) return '-';
        try {
            // id_ID = Bahasa Indonesia, @calendar=islamic-umalqura = Hitungan Resmi Arab Saudi
            $formatter = new \IntlDateFormatter(
                'id_ID@calendar=islamic-umalqura', 
                \IntlDateFormatter::NONE, 
                \IntlDateFormatter::NONE, 
                'Asia/Jakarta', 
                \IntlDateFormatter::TRADITIONAL
            );
            $formatter->setPattern('d MMMM yyyy'); // Menghasilkan tulisan Indonesia seperti "1 Zulhijah 1447"
            
            return $formatter->format(new \DateTime($tanggalMasehi)) . ' H';
        } catch (\Exception $e) {
            return '-';
        }
    };


    return $schema->components([
        
        // KOTAK UTAMA KIRI: PERIODE MASEHI
        Section::make('Periode Masehi')
            ->schema([
                DatePicker::make('startDate')
                    ->label('Dari')
                    ->default(now()->startOfMonth())
                    ->live()
                    // --- 1. OTOMATIS RUN SAAT HALAMAN BARU DIBUKA (FIRST LOAD) ---
                    ->afterStateHydrated(function ($state, callable $set) use ($konversiKeHijriah) {
                        $set('startHijri', $konversiKeHijriah($state));
                    })
                    // --- 2. RUN SAAT PENGGUNA MENGUBAH TANGGAL DI KALENDER ---
                    ->afterStateUpdated(function ($state, callable $set) use ($konversiKeHijriah) {
                        $set('startHijri', $konversiKeHijriah($state));
                    }),

                DatePicker::make('endDate')
                    ->label('Sampai')
                    ->default(now())
                    ->live()
                    // --- 1. OTOMATIS RUN SAAT HALAMAN BARU DIBUKA (FIRST LOAD) ---
                    ->afterStateHydrated(function ($state, callable $set) use ($konversiKeHijriah) {
                        $set('endHijri', $konversiKeHijriah($state));
                    })
                    // --- 2. RUN SAAT PENGGUNA MENGUBAH TANGGAL DI KALENDER ---
                    ->afterStateUpdated(function ($state, callable $set) use ($konversiKeHijriah) {
                        $set('endHijri', $konversiKeHijriah($state));
                    }),
            ])
            ->columns(2),

        // KOTAK UTAMA KANAN: PERIODE HIJRIAH (OTOMATIS LANGSUNG TERISI)
        Section::make('(Otomatis) Hijriah')
            ->schema([
                TextInput::make('startHijri')
                    ->label('Dari')
                    ->readOnly() // Dikunci agar user tidak bisa ketik manual
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800']), 

                TextInput::make('endHijri')
                    ->label('Sampai')
                    ->readOnly() // Dikunci agar user tidak bisa ketik manual
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-800']),
            ])
            ->columns(2),

    ]);
}


    protected function getHeaderActions(): array
    {
        return [
            // --- 1. TEMPLATE CSV (SESUAI FORMAT EXCEL ANDA) ---
            Action::make('download_template')
                ->label('Template')
                ->icon('heroicon-m-document-text')
                ->color('gray')
                ->action(function () {
                    return response()->streamDownload(function () {
                        $file = fopen('php://output', 'w');
                        fwrite($file, "sep=;\n");
                        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                        
                        fputcsv($file, ['ID', 'Tanggal Masehi', 'Tanggal Hijriah', 'Nama Transaksi', 'Kategori', 'Tipe', 'Nominal', 'Catatan'], ';');
                        fputcsv($file, ['', date('d/m/Y'), '', 'Gaji Simulasi3', 'Gaji Bulanan Suami (Pemasukan)', 'Pemasukan', '9000000', 'Simulasi'], ';');
                        
                        fclose($file);
                    }, "Template_Laporan_Syariah.csv");
                }),

            // --- 2. IMPORT LAPORAN (PAS DENGAN URUTAN KOLOM EXCEL) ---
            Action::make('import_transactions')
                ->label('Import Laporan')
                ->icon('heroicon-m-arrow-up-tray')
                ->color('info')
                ->form([
                    FileUpload::make('file_import')
                        ->label('Pilih File CSV')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->storeFiles(false),
                ])
                ->action(function (array $data) {
                    $file = $data['file_import'];
                    $filePath = $file->getRealPath();
                    
                    $f = fopen($filePath, 'r');
                    $firstLine = fgets($f);
                    $separator = str_contains($firstLine, ';') ? ';' : ',';
                    fclose($f);

                    $handle = fopen($filePath, 'r');
                    fgetcsv($handle, 1000, $separator); 
                    fgetcsv($handle, 1000, $separator); 
                    
                    DB::beginTransaction();
                    try {
                        $updatedCount = 0;
                        $createdCount = 0;

                        while (($row = fgetcsv($handle, 1000, $separator)) !== FALSE) {
                            if (empty($row) || !isset($row) || trim($row[1]) === '') continue;

                            $cleanId = !empty($row[0]) ? preg_replace('/[^0-9]/', '', $row[0]) : null;
                            $isExist = (!empty($cleanId)) ? Transaction::where('id', $cleanId)->exists() : false;
                            
                            $isExist ? $updatedCount++ : $createdCount++;

                            try {
                                $tanggalFix = Carbon::createFromFormat('d/m/Y', trim($row[1]))->format('Y-m-d');
                            } catch (\Exception $e) {
                                $tanggalFix = Carbon::parse(trim($row[1]))->format('Y-m-d');
                            }

                            $namaTransaksi = trim($row[3]);
                            $namaKategori = trim($row[4]);
                            $tipeTransaksi = trim($row[5]);
                            $nominalUang = (int) preg_replace('/[^0-9]/', '', $row[6]);
                            $catatan = trim($row[7] ?? '-');

                            $isExpenseInput = (trim($tipeTransaksi) === 'Pengeluaran');

                            $category = Category::firstOrCreate(
                                ['name' => $namaKategori],
                                [
                                    'is_expense' => $isExpenseInput,
                                    'type' => 'domestik'
                                ]
                            );

                            Transaction::updateOrCreate(
                                ['id' => (!empty($cleanId) && is_numeric($cleanId)) ? $cleanId : null],
                                [
                                    'date' => $tanggalFix,
                                    'name' => $namaTransaksi,
                                    'category_id' => $category->id,
                                    'is_expense' => $isExpenseInput,
                                    'amount' => $nominalUang,
                                    'note' => $catatan,
                                ]
                            );
                        }
                        
                        DB::commit();
                        Notification::make()
                            ->title("Import Selesai")
                            ->body("Berhasil: $updatedCount diperbarui, $createdCount data baru.")
                            ->success()->send();
                            
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Notification::make()->title("Gagal Import!")->body("Kesalahan: " . $e->getMessage())->danger()->send();
                    }
                    fclose($handle);
                }),

            // --- 3. UNDUH LAPORAN (REKATAN FINISHING KODE YANG TERPOTONG) ---
            Action::make('download_report')
                ->label('Unduh Laporan')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $start = $this->filters['startDate'] ?? now()->startOfMonth();
                    $end = $this->filters['endDate'] ?? now();

                    $transactions = Transaction::with('category')
                        ->whereBetween('date', [$start, $end])
                        ->orderBy('date', 'asc')
                        ->get();

                    if ($transactions->isEmpty()) {
                        Notification::make()->title("Data Tidak Ditemukan")->warning()->send();
                        return;
                    }

                    return response()->streamDownload(function () use ($transactions) {
                        $file = fopen('php://output', 'w');
                        fwrite($file, "sep=;\n");
                        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                        
                        fputcsv($file, ['ID', 'Tanggal Masehi', 'Tanggal Hijriah', 'Nama Transaksi', 'Kategori', 'Tipe', 'Nominal', 'Catatan'], ';');
                        
                        foreach ($transactions as $t) {
                            fputcsv($file, [
                                $t->id, 
                                Carbon::parse($t->date)->format('d/m/Y'), 
                                $t->date_hijri ?? '-', 
                                $t->name, 
                                $t->category?->name ?? '-', 
                                $t->is_expense ? 'Pengeluaran' : 'Pemasukan',
                                $t->amount,
                                $t->note ?? '-'
                            ], ';');
                        }
                        fclose($file);
                    }, "Laporan_Transaksi_Syariah.csv");
                }),
        ];
    }
}
