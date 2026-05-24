<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Mengunci 1 akun utama bernama Bismillah dengan peran super_admin agar aman & punya akses penuh
        User::firstOrCreate(
            ['email' => 'bismillah@bismillah.id'], // Email login utama Anda
            [
                'name' => 'Bismillah',
                'role' => 'super_admin', // 🌟 MENYETEL AKSES PERAN SEBAGAI SUPER ADMIN UTAMA
                'password' => Hash::make('bismillah123'), // Password untuk login
            ]
        );

        // Memanggil seeder kategori keuangan syariah dan master produk sepatu secara serentak
        $this->call([
            CategorySeeder::class,
            ProdukSeeder::class, // 🌟 MENYISIPKAN DATA SEED SEPATU SECARA OTOMATIS
        ]);
    }
}
