<?php

namespace App\Filament\Resources\Produks\RelationManagers;

use App\Models\StockLog;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class StockLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockLogs';
    protected static ?string $title = 'Riwayat Pergerakan Stok';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('jenis')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'penjualan' => 'danger',
                        'kulakan' => 'success',
                        'dihapus', 'dihapus_permanen' => 'gray',
                        'dipulihkan' => 'warning',
                        'koreksi_edit' => 'info',
                        'penyesuaian_manual' => 'amber',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'penjualan' => 'Penjualan',
                        'kulakan' => 'Kulakan',
                        'dihapus' => 'Transaksi Dihapus',
                        'dihapus_permanen' => 'Dihapus Permanen',
                        'dipulihkan' => 'Transaksi Dipulihkan',
                        'koreksi_edit' => 'Koreksi Edit',
                        'penyesuaian_manual' => 'Penyesuaian Manual',
                        default => $state,
                    }),

                TextColumn::make('perubahan')
                    ->label('Perubahan')
                    ->formatStateUsing(fn (int $state): string => ($state >= 0 ? '+' : '') . number_format($state, 0, ',', '.'))
                    ->color(fn (int $state): string => $state >= 0 ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('stok_sebelum')
                    ->label('Sebelum'),

                TextColumn::make('stok_sesudah')
                    ->label('Sesudah'),

                TextColumn::make('transaction.name')
                    ->label('Transaksi Terkait')
                    ->placeholder('-')
                    ->limit(30),

                TextColumn::make('user.name')
                    ->label('Oleh')
                    ->placeholder('Sistem'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->wrap(),
            ])
            // Log adalah jejak audit - sengaja tidak ada aksi edit/hapus per baris,
            // supaya riwayatnya tidak bisa diutak-atik. Koreksi dilakukan lewat
            // "Penyesuaian Stok" (menambah baris log baru), bukan mengubah yang lama.
            ->headerActions([
                HeaderAction::make('penyesuaian_stok')
                    ->label('Penyesuaian Stok')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        TextInput::make('stok_baru')
                            ->label('Stok Fisik Sekarang')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(fn () => $this->getOwnerRecord()->stok)
                            ->helperText('Isi dengan jumlah stok fisik hasil hitung ulang - selisihnya dengan stok sistem dicatat otomatis.'),

                        Textarea::make('keterangan')
                            ->label('Alasan Penyesuaian')
                            ->placeholder('Contoh: Stok opname bulanan, barang rusak, selisih packing')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            StockLog::catatPenyesuaianManual(
                                produkId: $this->getOwnerRecord()->id,
                                stokBaru: (int) $data['stok_baru'],
                                keterangan: $data['keterangan'],
                            );

                            Notification::make()
                                ->title('Stok berhasil disesuaikan')
                                ->success()
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title('Penyesuaian gagal')
                                ->body(collect($e->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([])
            ->bulkActions([]);
    }
}