<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * 🔒 GERBANG KEAMANAN MULTI-USER KELUARGA
     * Memastikan Anak juga terdaftar sebagai pengguna resmi yang boleh login panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Menyertakan 'anak' ke dalam barisan peran yang sah
        return in_array($this->role, ['super_admin', 'ayah', 'ibu', 'anak', 'kasir']);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
