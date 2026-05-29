<?php

namespace App\Livewire\User;

use App\Enums\License;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Settings extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public User $user;
    public array $data = [];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('realname')
                    ->label('Real Name')
                    ->disabled(),
                TextInput::make('name')
                    ->label('User Name')
                    ->disabled()
                    ->helperText('Contact Admin to change real and user names'),
                Select::make('license')
                    ->options(License::options())
                    ->selectablePlaceholder(false)
                    ->disabled()
                    ->helperText('Only CC-BY-4.0 is currently supported'),
                TextInput::make('email')
                    ->email()
                    ->required(),
                Toggle::make('mail_daily_digest')
                    ->label('Receive daily digest of tracked parts')
                    ->default(true)
                    ->required(),
                Select::make('timezone')
                    ->label('Timezone')
                    ->options(timezone_identifiers_list())
                    ->in(timezone_identifiers_list())
                    ->required(),
            ])
            ->statePath('data')
            ->model($this->user);
    }

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->form->fill($this->user->attributesToArray());
    }

    public function save(): void
    {
        $data = $this->form->getState();
        dd($data);
    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.forms.basic-form', ['submitRoute' => 'save']);
    }
}
