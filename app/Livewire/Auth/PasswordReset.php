<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class PasswordReset extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public ?array $data = [];

    public string $token = '';

    #[Url]
    public string $email = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->form->fill([
            'email' => $this->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->confirmed()
                    ->revealable()
                    ->required(),
                TextInput::make('password_confirmation')
                    ->label('Confirm Password')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function store(): void
    {
        $data = $this->form->getState();
        $data['token'] = $this->token;

        $status = Password::reset(
            $data,
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'data.email' => __($status)
            ]);
        }

        Notification::make()
            ->title('Password reset successfully.')
            ->success()
            ->send();

        $this->redirectRoute('login');
    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.forms.basic-form', ['submitRoute' => 'store']);
    }
}
