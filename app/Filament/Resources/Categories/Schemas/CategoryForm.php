<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Kategori')
                    ->required()
                    ->placeholder('Contoh: Belanja Sembako, Modal Bisnis, Infak')
                    ->unique(ignoreRecord: true),

                Toggle::make('is_expense')
                    ->label('Kategori Pengeluaran?')
                    ->required()
                    ->default(true),

                // Tambahan komponen baru sesuai klasifikasi 3 pilar Islami
                ToggleButtons::make('type')
                    ->label('Klasifikasi Kategori')
                    ->options([
                        'rumah_tangga' => 'Rumah Tangga',
                        'tijarah' => 'Tijarah (Bisnis)',
                        'tabarru' => 'Tabarru’ (Social)',
                    ])
                    ->colors([
                        'rumah_tangga' => 'info',
                        'tijarah' => 'success',
                        'tabarru' => 'warning',
                    ])
                    ->default('rumah_tangga')
                    ->inline()
                    ->required(),
            ]);
    }
}
