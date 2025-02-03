<?php

namespace App\Livewire;

use App\Models\MybbUser;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Component;

class JoinLdraw extends Component implements HasForms
{
    use InteractsWithForms;

    public bool $meetsId = false;
    public ?array $data = [];

    public function mount()
    {
        $u = MybbUser::findFromCookie();
        if (is_null($u)) {
            $this->redirect('/login');
            return;
        }
        if ($u->inGroup(config('ldraw.mybb-groups')['LDraw Member'])) {
            $this->redirectIntended();
        }
        $this->meetsId = $u->inGroup(config('ldraw.mybb-groups')['Registered'])
            || $u->inGroup(config('ldraw.mybb-groups')['Administrators']);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Read Bylaws')
                        ->schema([
                            View::make('documents.bylaws'),
                        ]),
                    Wizard\Step::make('Acknowledge and Join')
                        ->schema([
                            Checkbox::make('age-check')
                                ->label("I affirm that I am at the age of majority for my country (typically 18 but local laws may vary)")
                                ->required(),
                            Checkbox::make('person-check')
                                ->label("I affirm that I am natural person (i.e. an individual human and not a proxy for a corporation or other entity")
                                ->required(),
                            Checkbox::make('bylaw-accept')
                                ->label("I affirm the goals of the the LDraw.org Bylaws and will abide by it's requirements")
                                ->required(),
                        ]),
                ])
                ->submitAction(new HtmlString(Blade::render("<x-filament::button type=\"submit\">\n<x-filament::loading-indicator wire:loading wire:target=\"joinOrg\" class=\"h-5 w-5\" />\nSubmit\n</x-filament::button>")))
            ])
            ->statePath('data');
    }

    public function joinOrg()
    {
        if (Arr::has($this->data, 'age-check')
            && Arr::has($this->data, 'person-check')
            && Arr::has($this->data, 'bylaw-accept')) {
            $u = MybbUser::findFromCookie();
            if ($u->additionalgroups == '') {
                $u->additionalgroups = config('ldraw.mybb-groups')['LDraw Member'];
            } else {
                $u->additionalgroups .= ',' . config('ldraw.mybb-groups')['LDraw Member'];
            }
            $u->save();
            Notification::make()
                ->title('Membership Successful')
                ->success()
                ->send();
            return $this->redirectIntended();
        }
        Notification::make()
            ->title('An error occured')
            ->error()
            ->send();
    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.join-ldraw');
    }
}
