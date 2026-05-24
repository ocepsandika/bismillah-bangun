<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Filament\Resources\Assets\RelationManagers\LogsRelationManager; // Import Sub-Menu Log Baru Anda
use App\Models\Asset;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    // --- STRUKTUR NAVIGASI SIDEBAR COMPATIBLE ---
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $recordTitleAttribute = 'Asset';

    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Aset';
    protected static string|null $navigationLabel = 'Daftar Aset Fisik';

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table);
    }

    // --- KUNCI PENDAFTARAN SUB-MENU LOGS DI SINI ---
    public static function getRelations(): array
    {
        return [
            LogsRelationManager::class, // Sub-menu sekarang resmi aktif di bawah form detail aset
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
