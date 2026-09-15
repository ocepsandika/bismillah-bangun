<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $title = 'Riwayat Perawatan & Perkembangan Fisik Aset';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal Aktivitas')
                    ->required()
                    ->default(now()),

                Select::make('jenis_log')
                    ->label('Jenis Aktivitas')
                    ->required()
                    ->options([
                        'biaya_perawatan' => 'Biaya Perawatan (Pupuk/Pakan/Upah Kerja - Kapitalisasi Modal)',
                        'perkembangan_fisik' => 'Catatan Perkembangan Fisik (Tinggi Pohon / Berat Sapi)',
                        'vaksin_obat' => 'Vaksin & Kesehatan Hewan',
                        'catatan_lainnya' => 'Catatan Tambahan Lainnya',
                    ]),

                TextInput::make('biaya_keluar')
                    ->label('Biaya yang Dikeluarkan')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->default(0)
                    ->helperText('Isi 0 jika hanya mencatat perkembangan fisik tanpa aliran uang tunai.'),

                TextInput::make('keterangan')
                    ->label('Detail Catatan Lapangan')
                    ->placeholder('Contoh: Pemupukan awal padi 50kg, Penyuntikan obat PMK, Berat sapi naik jadi 450kg')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tanggal')
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d-m-Y')
                    ->sortable(),

                TextColumn::make('jenis_log')
                    ->label('Jenis Aktivitas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'biaya_perawatan' => 'danger',
                        'vaksin_obat' => 'warning',
                        'perkembangan_fisik' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'biaya_perawatan' => 'Biaya Perawatan',
                        'vaksin_obat' => 'Vaksin/Obat',
                        'perkembangan_fisik' => 'Fisik/Timbangan',
                        default => 'Lainnya'
                    }),

                TextColumn::make('biaya_keluar')
                    ->label('Biaya Melekat')
                    ->money('IDR', locale: 'id_ID'),

                TextColumn::make('keterangan')
                    ->label('Detail Keterangan')
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()->label('Tambah Catatan Perawatan'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
