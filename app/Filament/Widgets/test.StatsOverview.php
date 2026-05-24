<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon; // Import Carbon

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;
    

   protected function getStats(): array
    {
        // Jika filter kosong, Carbon otomatis ambil awal bulan ini & hari ini
        $startDate = $this->filters['startDate'] 
            ? \Illuminate\Support\Carbon::parse($this->filters['startDate']) 
            : \Illuminate\Support\Carbon::now()->startOfMonth(); // Tanggal 1 bulan ini

        $endDate = $this->filters['endDate'] 
            ? \Illuminate\Support\Carbon::parse($this->filters['endDate']) 
            : \Illuminate\Support\Carbon::now(); // Hari ini

        $query = Transaction::query();

        // Sekarang query WAJIB mengikuti rentang tanggal di atas
        $query->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate);

        $income = (clone $query)->whereHas('category', fn($q) => $q->where('is_expense', false))->sum('amount');
        $expense = (clone $query)->whereHas('category', fn($q) => $q->where('is_expense', true))->sum('amount');
        $balance = $income - $expense;

        // 3. Membuat Label yang Seragam
        $periodeLabel = 'Periode: ' . $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M');


        // 4. Return (Mengganti yang lama)
        return [
            Stat::make('Status Sistem', 'Normal')
        ->description('Database Terkoneksi')
        ->descriptionIcon('heroicon-m-check-circle')
        ->color('success'),
            Stat::make('Total Pemasukan', 'Rp ' . number_format($income, 0, ',', '.'))
                ->color('success'),

            Stat::make('Total Pengeluaran', 'Rp ' . number_format($expense, 0, ',', '.'))
                ->color('danger'),

            Stat::make('Sisa Saldo', 'Rp ' . number_format($balance, 0, ',', '.'))
                ->description($periodeLabel)
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}