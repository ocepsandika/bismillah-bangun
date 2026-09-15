<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TrenBulananPilarChart extends ChartWidget
{
    protected ?string $heading = 'Tren Bulanan Pemasukan vs Pengeluaran';

    // Jumlah bulan ke belakang yang ditampilkan di grafik
    protected int $jumlahBulan = 6;

    public ?string $filter = 'rumah_tangga';

    /**
     * Dropdown filter pilar di pojok kanan-atas widget, supaya grafik tetap
     * ringkas (2 garis: pemasukan & pengeluaran) alih-alih 6 garis sekaligus
     * kalau ketiga pilar ditampilkan bersamaan.
     */
    protected function getFilters(): ?array
    {
        return [
            'rumah_tangga' => 'Rumah Tangga (Al-Ma\'isyah)',
            'tijarah' => 'Tijarah (Usaha)',
            'tabarru' => "Tabarru' (Sosial)",
        ];
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $isSuperAdmin = ($user && $user->role === 'super_admin');
        $pilar = $this->filter ?? 'rumah_tangga';

        // 6 bulan terakhir termasuk bulan berjalan, urut dari yang paling lama
        $bulanBulan = collect(range($this->jumlahBulan - 1, 0))
            ->map(fn ($mundur) => now()->subMonths($mundur)->startOfMonth());

        $labels = $bulanBulan->map(fn (Carbon $bulan) => $bulan->translatedFormat('M Y'))->toArray();

        $pemasukan = [];
        $pengeluaran = [];

        foreach ($bulanBulan as $bulan) {
            $awal = $bulan->copy()->startOfMonth()->toDateString();
            $akhir = $bulan->copy()->endOfMonth()->toDateString();

            $query = Transaction::whereHas('category', fn ($q) => $q->where('type', $pilar))
                ->whereBetween('date', [$awal, $akhir])
                ->when(! $isSuperAdmin, fn ($q) => $q->where('user_id', $user->id));

            $pemasukan[] = (clone $query)->where('is_expense', false)->sum('amount');
            $pengeluaran[] = (clone $query)->where('is_expense', true)->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $pemasukan,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $pengeluaran,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}