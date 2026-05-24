<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    // Trik Terringan: Mengisi status is_expense secara otomatis tepat sebelum data masuk ke database
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['category_id'])) {
            // Cari data kategori berdasarkan ID yang dipilih user di form
            $category = Category::find($data['category_id']);
            
            if ($category) {
                // Set kolom is_expense pada transaksi mengikuti nilai is_expense milik Kategori
                $data['is_expense'] = $category->is_expense;
            }
        }

        return $data;
    }
}
