<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ForgotPassword extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function store(): void
    {
        $data = $this->form->getState();

        Password::sendResetLink($data);

        Notification::make()
            ->title('If that email exists, a reset link has been sent.')
            ->success()
            ->send();

        $this->form->fill();
        $this->redirectRoute('login');
    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.forms.basic-form', ['submitRoute' => 'store']);
    }
}
