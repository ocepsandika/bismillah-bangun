<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\TransactionResource;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    // Trik Terringan: Perbarui status is_expense jika user mengubah kategori saat proses edit
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['category_id'])) {
            // Ambil data kategori berdasarkan ID yang baru dipilih oleh user
            $category = Category::find($data['category_id']);
            
            if ($category) {
                // Sinkronkan status is_expense transaksi mengikuti Kategori terbaru
                $data['is_expense'] = $category->is_expense;
            }
        }

        return $data;
    }
}
