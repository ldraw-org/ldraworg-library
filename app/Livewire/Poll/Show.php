<?php

namespace App\Livewire\Poll;

use Filament\Schemas\Schema;
use Closure;
use App\Models\Poll\Poll;
use App\Models\Poll\PollVote;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public Poll $poll;

    public function mount(): void
    {
        $this->authorize('vote', $this->poll);
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                CheckboxList::make('votes')
                    ->label("Choose up to {$this->poll->choices_limit} option(s)")
                    ->options($this->poll->items->pluck('item', 'id')->all())
                    ->allowHtml()
                    ->required()
                    ->rules([
                       fn (): Closure => function (string $attribute, $value, Closure $fail) {
                           if (count($value) > $this->poll->choices_limit) {
                               $fail("You can only choose up to {$this->poll->choices_limit} option(s)");
                           }
                       },
                    ])
                    ->validationMessages([
                       'required' => 'You choose at least one option',
                    ])
            ])
            ->statePath('data');
    }

    public function castVote()
    {
        $this->form->getState();
        foreach ($this->data['votes'] as $id => $item_id) {
            PollVote::create([
                'poll_item_id' => $item_id,
                'user_id' => Auth::user()->id,
            ]);
        }
        Notification::make()
        ->title('Poll Vote Cast')
        ->success()
        ->send();
        $this->redirectRoute('index');

    }

    #[Layout('components.layout.base')]
    public function render()
    {
        return view('livewire.poll.show');
    }
}
