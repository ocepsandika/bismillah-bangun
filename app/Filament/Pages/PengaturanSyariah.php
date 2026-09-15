<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Halaman pengaturan syariah (saat ini: harga emas per gram).
 * Mengikuti pola resmi "singular resource" Filament - satu baris data,
 * bukan tabel/list. Lihat: https://filamentphp.com/docs/5.x/resources/singular
 *
 * @property-read Schema $form
 */
class PengaturanSyariah extends Page
{
    protected string $view = 'filament.pages.pengaturan-syariah';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Pengaturan Syariah';
    protected static ?string $title = 'Pengaturan Syariah';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaForm::make([
                    TextInput::make('harga_emas_per_gram')
                        ->label('Harga emas per gram (Rp)')
                        ->helperText('Dipakai untuk menghitung batas nishab zakat maal (85 gram emas) di dashboard. Update angka ini sesuai harga emas terkini supaya perhitungan zakat tetap akurat.')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->prefix('Rp'),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $record = $this->getRecord();
        $record->fill($data);
        $record->save();

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan')
            ->send();
    }

    public function getRecord(): Setting
    {
        return Setting::current();
    }
}