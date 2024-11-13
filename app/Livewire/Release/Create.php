<?php

namespace App\Livewire\Release;

use App\Jobs\MakePartRelease;
use App\Models\Part\Part;
use App\Models\Part\PartRelease;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Create extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::unofficial()
                ->where('vote_sort', 1)
                ->orderBy('part_type_id')
                ->orderBy('filename')
            )
            ->emptyState(view('tables.empty', ['none' => 'None']))
            ->columns([
                Split::make([
                    ToggleColumn::make('marked_for_release')
                        ->grow(false),
                    ImageColumn::make('image')
                        ->state(
                            fn (Part $p): string => asset("images/library/{$p->libFolder()}/" . substr($p->filename, 0, -4) . '_thumb.png')
                        )
                        ->grow(false)
                        ->extraImgAttributes(['class' => 'object-scale-down w-[35px] max-h-[75px]']),
                    Stack::make([
                        TextColumn::make('filename')
                            ->weight(FontWeight::Bold)
                            ->sortable(),
                        TextColumn::make('description')
                            ->sortable(),
                    ])->alignment(Alignment::Start),
                    Stack::make([
                        ViewColumn::make('vote_sort')
                            ->view('tables.columns.part-status')
                            ->sortable()
                            ->grow(false)
                            ->label('Status'),
                        TextColumn::make('part_check_messages')
                            ->state(fn (Part $part) => implode(", ", $part->part_check_messages['errors']))
                            ->wrap()
                            ->alignment(Alignment::End),
                    ])->alignment(Alignment::End),
                ])->from('md')
            ])
            ->recordClasses(fn (Part $p) => count($p->part_check_messages['errors']) > 0 ? '!bg-red-300' : null)
            ->actions([
                Action::make('view')
                    ->url(fn (Part $p) => route('parts.show', $p))
                    ->button()
            ])
            ->headerActions([
                Action::make('create-release')
                    ->form([
                        Toggle::make('include-ldconfig'),
                        FileUpload::make('additional-files')
                    ])
                    ->action(fn (array $data) => $this->createRelease($data))
                    ->successRedirectUrl(route('tracker.activity')),
                Action::make('reset-marked-parts')
                    ->action(function () {
                        Part::unofficial()->where('can_release', false)->where('marked_for_release', true)->update([
                            'marked_for_release' => false
                        ]);
                        Part::unofficial()
                            ->where('can_release', true)
                            ->where('vote_sort', 1)
                            ->update([
                                'marked_for_release' => true
                            ]);
                    })
            ]);
    }

    protected function createRelease(array $data): void
    {
        $this->authorize('store', PartRelease::class);
        $addFiles = [];
        if (!is_null($data['additional-files'])) {
            foreach ($data['additional-files'] as $afile) {
                $addFiles[$afile->getClientOriginalName()] = $afile->get();
            }
        }
        $parts = Part::unofficial()->where('marked_for_release', true)->get();
        MakePartRelease::dispatch($parts, Auth::user(), $data['include-ldconfig'] ?? false, $addFiles);
        $this->redirectRoute('tracker.activity');
    }
    #[Layout('components.layout.tracker')]
    public function render()
    {
        return view('livewire.release.create');
    }
}
