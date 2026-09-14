<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTransactionSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Category::create([
            'name' => 'Pembelian Aset Simpanan Pribadi (Emas/Tanah/Ternak)',
            'is_expense' => true,
            'type' => 'rumah_tangga',
        ]);
        Category::create([
            'name' => 'Penjualan Aset Simpanan Pribadi (Emas/Tanah/Ternak)',
            'is_expense' => false,
            'type' => 'rumah_tangga',
        ]);
        Category::create([
            'name' => 'Biaya Operasional Bisnis',
            'is_expense' => true,
            'type' => 'tijarah',
        ]);
    }

    public function test_asset_transactions_use_foreign_keys_and_source(): void
    {
        $asset = Asset::create([
            'name' => 'Emas Keluarga',
            'peruntukan' => 'qunyah',
            'tanggal_beli' => '2026-05-01',
            'harga_beli' => 1_000_000,
            'nilai_pasar_sekarang' => 1_200_000,
        ]);

        $this->assertTrue($asset->wasRecentlyCreated);

        $this->assertDatabaseHas('transactions', [
            'asset_id' => $asset->id,
            'source' => 'asset_buy',
            'amount' => 1_000_000,
            'is_expense' => true,
        ]);

        $asset->lokasi_keterangan = 'Lemari';
        $asset->save();

        $this->assertSame(1, Transaction::count());

        $asset->update([
            'status_aset' => 'terjual',
            'tanggal_jual' => '2026-06-01',
            'nilai_pasar_sekarang' => 1_500_000,
            'persentase_milik_pribadi' => 60,
        ]);

        $this->assertDatabaseHas('transactions', [
            'asset_id' => $asset->id,
            'source' => 'asset_sell',
            'amount' => 900_000,
            'is_expense' => false,
        ]);
    }

    public function test_saldo_awal_and_maintenance_zero_use_the_new_linkage(): void
    {
        $saldoAwal = Asset::create([
            'name' => 'Emas Warisan',
            'is_saldo_awal' => true,
            'peruntukan' => 'qunyah',
            'tanggal_beli' => '2026-01-01',
            'harga_beli' => 500_000,
            'nilai_pasar_sekarang' => 550_000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'asset_id' => $saldoAwal->id,
            'source' => 'asset_buy',
            'is_expense' => false,
        ]);

        $asset = Asset::create([
            'name' => 'Sapi Usaha',
            'peruntukan' => 'tijarah',
            'harga_beli' => 1_000,
            'nilai_pasar_sekarang' => 1_500,
        ]);
        $log = AssetLog::create([
            'asset_id' => $asset->id,
            'tanggal' => '2026-05-10',
            'jenis_log' => 'biaya_perawatan',
            'biaya_keluar' => 200,
            'keterangan' => 'Pakan',
        ]);

        $transaction = Transaction::where('asset_log_id', $log->id)->firstOrFail();

        $this->assertSame('asset_maintenance', $transaction->source);
        $this->assertSame($asset->id, $transaction->asset_id);
        $this->assertSame(1_200, $asset->fresh()->harga_beli);

        $log->update(['biaya_keluar' => 0]);

        $this->assertSame(1_000, $asset->fresh()->harga_beli);
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    }

    public function test_hijri_fields_are_generated_from_the_transaction_date(): void
    {
        $category = Category::firstOrFail();

        $transaction = Transaction::create([
            'name' => 'Transaksi uji',
            'category_id' => $category->id,
            'is_expense' => true,
            'date' => '2026-05-01',
            'amount' => 10_000,
        ]);

        $this->assertNotNull($transaction->date_hijri);
        $this->assertGreaterThan(0, $transaction->month_hijri);
        $this->assertGreaterThan(0, $transaction->year_hijri);
    }

    public function test_deleting_a_log_soft_deletes_its_automatic_transaction(): void
    {
        $asset = Asset::create([
            'name' => 'Kambing Usaha',
            'peruntukan' => 'tijarah',
            'harga_beli' => 1_000,
            'nilai_pasar_sekarang' => 1_500,
        ]);
        $log = AssetLog::create([
            'asset_id' => $asset->id,
            'jenis_log' => 'biaya_perawatan',
            'biaya_keluar' => 200,
            'keterangan' => 'Vaksin',
        ]);
        $transaction = Transaction::where('asset_log_id', $log->id)->firstOrFail();

        $log->delete();

        $this->assertSame(1_000, $asset->fresh()->harga_beli);
        $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
        $this->assertSame($log->id, Transaction::withTrashed()->findOrFail($transaction->id)->asset_log_id);
    }
}
