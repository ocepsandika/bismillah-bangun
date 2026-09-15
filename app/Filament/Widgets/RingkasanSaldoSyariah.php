<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters; // <-- WAKIL WAJIB UNTUK FILAMENT v5
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Budget;
use App\Models\Asset;
use App\Models\Setting;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class RingkasanSaldoSyariah extends BaseWidget
{
    // Mengaktifkan fitur pemantauan filter dari halaman dashboard utama secara live
    use InteractsWithPageFilters; // <-- PASTI KAN INI SUDAH DITULIS

    protected ?string $pollingInterval = '10s';
    protected int | array | null $columns = 3; 
    protected array | string | int $columnSpan = 'full'; 

    protected function getStats(): array
    {
        // --- 1. AMBIL NILAI FILTER DARI KALENDER MASEHI DASHBOARD ---
        $startDate = $this->filters['startDate'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->filters['endDate'] ?? now()->format('Y-m-d');

        $formatterHijri = new \IntlDateFormatter(
            'id_ID@calendar=islamic-umalqura', 
            \IntlDateFormatter::NONE, 
            \IntlDateFormatter::NONE, 
            'Asia/Jakarta', 
            \IntlDateFormatter::TRADITIONAL
        );
        
        $formatterHijri->setPattern('d MMMM yyyy');
        
        $mulaiHijri = $formatterHijri->format(new \DateTime($startDate)) . ' H';
        $selesaiHijri = $formatterHijri->format(new \DateTime($endDate)) . ' H';
        $teksPeriodeHijri = "Periode: {$mulaiHijri} s/d {$selesaiHijri}";

        // --- 🔒 BARIS PROTEKSI BARU MULTI-USER BERBASIS PERAN (ROLE) ---
        $user = auth()->user();
        $isSuperAdmin = ($user && $user->role === 'super_admin');

        // --- 2. LOGIKA HITUNG MUNDUR SISA ANGGARAN BELANJA DAPUR ---
        $kategoriDapur = Category::where('name', 'LIKE', '%Belanja Dapur%')->first();
        
        $totalTerpakaiDapur = 0;
        if ($kategoriDapur) {
            $totalTerpakaiDapur = Transaction::where('category_id', $kategoriDapur->id)
                ->where('is_expense', true)
                ->whereBetween('date', [$startDate, $endDate])
                ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
                ->sum('amount');
        }

        $plafonDapur = Budget::when($kategoriDapur, function($query) use ($kategoriDapur) {
                return $query->where('category_id', $kategoriDapur->id);
            })
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereDate('start_date', '<=', $endDate)
                      ->whereDate('end_date', '>=', $startDate);
            })
            ->value('plafon_anggaran') ?? 0;

        $sisaAnggaranDapur = $plafonDapur - $totalTerpakaiDapur;

        // --- 3. HITUNGAN SALDO REKAPUTASI KAS 3 PILAR HARTA ---
        $pemasukanRT = Transaction::whereHas('category', function($q) {
                $q->where('type', 'rumah_tangga')->where('name', '!=', 'Saldo Awal Aset Simpanan Pribadi Historis');
            })
            ->where('is_expense', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $pengeluaranRT = Transaction::whereHas('category', fn($q) => $q->where('type', 'rumah_tangga'))
            ->where('is_expense', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $saldoRumahTangga = $pemasukanRT - $pengeluaranRT;
        $pemasukanTijarah = Transaction::whereHas('category', function($q) {
                $q->where('type', 'tijarah')->where('name', '!=', 'Saldo Awal Aset Produktif Bisnis Historis');
            })
            ->where('is_expense', false)
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $pengeluaranTijarah = Transaction::whereHas('category', fn($q) => $q->where('type', 'tijarah'))
            ->where('is_expense', true)
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $saldoTijarah = $pemasukanTijarah - $pengeluaranTijarah;

        $totalSyubhat = Transaction::whereHas('category', fn($q) => $q->where('name', 'Penerimaan Dana Syubhat / Non-Halal'))
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $totalPembersihan = Transaction::whereHas('category', fn($q) => $q->where('name', 'Pembersihan Harta (Fasilitas Umum)'))
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $sisaSyubhatWajibBersih = $totalSyubhat - $totalPembersihan;

        // --- 4. KONTROL AKUNTANSI UTANG & PIUTANG ---
        $utangMasuk = Transaction::whereHas('category', fn($q) => $q->where('name', 'Penerimaan Pinjaman Utang Masuk'))
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $utangDibayar = Transaction::whereHas('category', fn($q) => $q->where('name', 'Pembayaran Cicilan/Pelunasan Utang'))
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $sisaUtangWajibLunas = $utangMasuk - $utangDibayar;

        $piutangKeluar = Transaction::whereHas('category', fn($q) => $q->where('name', 'Pengeluaran Meminjamkan Uang ke Orang'))
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $piutangDiterima = Transaction::whereHas('category', fn($q) => $q->where('name', 'Penerimaan Pelunasan Piutang dari Orang'))
            ->whereBetween('date', [$startDate, $endDate])
            ->when(!$isSuperAdmin, fn($query) => $query->where('user_id', $user->id)) // <-- KUNCI DATA DISINI
            ->sum('amount');
            
        $sisaPiutangDiOrang = $piutangKeluar - $piutangDiterima;

        // --- 5. LOGIKA VALUASI HARTA BERDASARKAN PERUNTUKAN FIKIH ---
        $totalAsetQunyah = Asset::where('status_aset', 'aktif')->where('peruntukan', 'qunyah')->sum('nilai_pasar_sekarang');
        $totalAsetTijarah = Asset::where('status_aset', 'aktif')->where('peruntukan', 'tijarah')->sum('nilai_pasar_sekarang');
        $totalKekayaanGabungan = $totalAsetQunyah + $totalAsetTijarah;

        // --- 6. ASPEK SYARIAH: ALARM HISAB NISHAZ ZAKAT PERNIAGAAN ---
        $hargaEmasPerGram = Setting::current()->harga_emas_per_gram;
        $batasNishabTahunIni = 85 * $hargaEmasPerGram;
        $basisSaldoTijarah = $saldoTijarah > 0 ? $saldoTijarah : 0;
        $totalHartaWajibZakat = $basisSaldoTijarah + $totalAsetTijarah;
        
        $nominalZakatMaal = 0;
        $statusZakatText = "Belum mencapai batas nishab haul";
        $zakatColor = 'gray';

        if ($totalHartaWajibZakat >= $batasNishabTahunIni) {
            $nominalZakatMaal = $totalHartaWajibZakat * 0.025; 
            $statusZakatText = "Wajib Zakat Maal Terpenuhi!";
            $zakatColor = 'danger'; 
        }

        // --- FUNGSI BANTUAN CSS INLINE NOMINAL ---
        $formatNominal = function($angka) {
            $teks = 'Rp ' . number_format($angka, 0, ',', '.');
            return new HtmlString("<span style='font-size: 1.25rem !important; font-weight: 700; letter-spacing: -0.025em;'>{$teks}</span>");
        };

        return [
            // BARIS 1: MONITOR ANGGARAN & KEKAYAAN TOTAL GABUNGAN
            Stat::make('Sisa Anggaran Dapur', $formatNominal($sisaAnggaranDapur))
                ->description($teksPeriodeHijri) 
                ->color($sisaAnggaranDapur >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-shopping-bag'),

            Stat::make('Total Kekayaan Fisik (Gabungan)', $formatNominal($totalKekayaanGabungan))
                ->description('Akumulasi nilai pasar bersih seluruh aset riil Anda')
                ->color('success')
                ->icon('heroicon-o-briefcase'),

            Stat::make('Kewajiban Zakat Maal (2.5%)', $formatNominal($nominalZakatMaal))
                ->description($statusZakatText)
                ->color($zakatColor)
                ->icon('heroicon-o-heart'),

            // BARIS 2: PEMISAHAN KELOMPOK HARTA SYARIAH
            Stat::make('Valuasi Komoditas Bisnis (Tijarah)', $formatNominal($totalAsetTijarah))
                ->description('Total nilai aset komoditas wajib perhitungan zakat')
                ->color('amber')
                ->icon('heroicon-o-circle-stack'),

            Stat::make('Valuasi Harta Pribadi (Qunyah)', $formatNominal($totalAsetQunyah))
                ->description('Nilai motor/simpanan konsumsi pribadi (Bebas Zakat)')
                ->color('gray')
                ->icon('heroicon-o-home-modern'),

            Stat::make('Indikator Dana Syubhat', $formatNominal($sisaSyubhatWajibBersih))
                ->description($sisaSyubhatWajibBersih > 0 ? 'Wajib segera disalurkan ke fasilitas umum!' : 'Bersih mutlak dari harta haram')
                ->color($sisaSyubhatWajibBersih > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),

            // BARIS 3: AKUNTANSI KAS OPERASIONAL TUNAI & LIABILITAS UTANG
            Stat::make('Rumah Tangga (Al-Ma\'isyah)', $formatNominal($saldoRumahTangga))
                ->description($teksPeriodeHijri) 
                ->color($saldoRumahTangga >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-home'),

            Stat::make('Modal & Profit Usaha (Tijarah)', $formatNominal($saldoTijarah))
                ->description($teksPeriodeHijri) 
                ->color($saldoTijarah >= 0 ? 'warning' : 'danger')
                ->icon('heroicon-o-briefcase'),

            Stat::make('Sisa Utang Anda ke Orang', $formatNominal($sisaUtangWajibLunas))
                ->description('Kewajiban janji hutang pribadi yang wajib ditunaikan')
                ->color($sisaUtangWajibLunas > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-arrow-trending-down'),
        ];
    }
}
