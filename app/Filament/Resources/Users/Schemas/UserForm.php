<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Petugas / Anggota Keluarga')
                    ->required(),

                Select::make('role')
                    ->label('Hak Akses Peran (Role)')
                    ->required()
                    ->options([
                        'ayah' => 'Ayah (Akses Mandiri)',
                        'ibu' => 'Ibu (Akses Mandiri)',
                        'anak' => 'Anak (Akses Belajar Mandiri)',
                        'kasir' => 'Staf Kasir Toko / Karyawan',
                        'super_admin' => 'Super Admin (Akses Penuh)',
                    ])
                    ->default('kasir'),

                TextInput::make('email')
                    ->label('Alamat Email Login')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->label('Password Akun')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->maxLength(255),
            ]);
    }
}
