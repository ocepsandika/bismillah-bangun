<?php

namespace App\Filament\Resources\Budgets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Group; 
use Filament\Schemas\Components\Grid;  
use Filament\Schemas\Schema;
use App\Models\Category;
use Illuminate\Support\HtmlString;

class BudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        $konversiKeHijriah = function ($tanggalMasehi) {
            if (! $tanggalMasehi || ! class_exists(\IntlDateFormatter::class)) return '---';
            try {
                $formatter = new \IntlDateFormatter(
                    'id_ID@calendar=islamic-umalqura', 
                    \IntlDateFormatter::NONE, 
                    \IntlDateFormatter::NONE, 
                    'Asia/Jakarta', 
                    \IntlDateFormatter::TRADITIONAL
                );
                $formatter->setPattern('d MMMM yyyy');
                return $formatter->format(new \DateTime($tanggalMasehi)) . ' H';
            } catch (\Throwable $e) {
                return '---';
            }
        };

        return $schema
            ->components([
                // 1. Baris Pertama: Kategori Pengeluaran (Lebar Penuh)
                Select::make('category_id')
                    ->label('Kategori Pengeluaran')
                    ->required()
                    ->options(fn () => Category::where('type', 'rumah_tangga')->where('is_expense', true)->pluck('name', 'id'))
                    ->searchable(),

                // 2. Baris Kedua: Tanggal Mulai & Selesai (Sejajar ke Samping, Ukuran Besar)
                Grid::make(2)
                    ->schema([
                        Group::make([
                            DatePicker::make('start_date')
                                ->label('Tanggal Mulai Periode')
                                ->required()
                                ->native(false)
                                ->live() 
                                ->maxDate(fn ($get) => $get('end_date')),

                            Placeholder::make('start_hijri_preview')
                                ->hiddenLabel()
                                ->content(fn ($get) => new HtmlString(
                                    "<div style='margin-top: 4px;' class='text-sm font-medium text-gray-500 dark:text-gray-400'>
                                        <span class='text-primary-600 dark:text-primary-400' style='font-weight:600;'>{$konversiKeHijriah($get('start_date'))}</span>
                                    </div>"
                                )),
                        ]),

                        Group::make([
                            DatePicker::make('end_date')
                                ->label('Tanggal Selesai Periode')
                                ->required()
                                ->native(false)
                                ->live() 
                                ->minDate(fn ($get) => $get('start_date')),

                            Placeholder::make('end_hijri_preview')
                                ->hiddenLabel()
                                ->content(fn ($get) => new HtmlString(
                                    "<div style='margin-top: 4px;' class='text-sm font-medium text-gray-500 dark:text-gray-400'>
                                        <span class='text-primary-600 dark:text-primary-400' style='font-weight:600;'>{$konversiKeHijriah($get('end_date'))}</span>
                                    </div>"
                                )),
                        ]),
                    ]),

                // 3. Baris Ketiga: Menggunakan Grid pembagi ruang kosong agar kotak anggaran naik ke atas
                Grid::make(2)
                    ->schema([
                        TextInput::make('plafon_anggaran')
                            ->label('Batas Maksimal Anggaran')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Tentukan batas uang maksimal untuk kategori ini dalam rentang tanggal berjalan.'),
                        
                        // Menyisipkan Placeholder kosong di sebelah kanan agar seimbang dengan kolom tanggal di atasnya
                        Placeholder::make('space_filler')->hiddenLabel(),
                    ]),
            ]);
    }
}
