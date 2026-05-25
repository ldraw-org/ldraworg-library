<?php

namespace App\Livewire\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component implements HasSchemas
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
                TextInput::make('login')
                    ->label('Username/Email')
                    ->required(),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required(),
                Toggle::make('remember')
                    ->label('Remember me')
                    ->default(true)

            ])
            ->statePath('data');
    }

    public function login(): void
    {
        $data = $this->form->getState();

        $throttleKey = Str::transliterate(Str::lower($data['login']) . '|' . request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'data.login' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        if (!Auth::attempt([$field => $data['login'], 'password' => $data['password']], $data['remember'])) {
            throw ValidationException::withMessages([
                'data.login' => 'Invalid credentials.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        request()->session()->regenerate();
        $this->redirectIntended();
    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.forms.basic-form', ['submitRoute' => 'login']);
    }
}
