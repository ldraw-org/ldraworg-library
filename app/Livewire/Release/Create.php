<?php

namespace App\Livewire\Release;

use App\Enums\CheckType;
use App\Enums\PartStatus;
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

    public function mount(): void
    {
        $this->authorize('create', PartRelease::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Part::unofficial()
                ->where('part_status', PartStatus::Certified)
                ->orderBy('type')
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
                        ViewColumn::make('part_status')
                            ->view('tables.columns.part-status')
                            ->sortable()
                            ->grow(false)
                            ->label('Status'),
                            
                        TextColumn::make('part_check')
                            ->state(fn (Part $part) => $part->part_check->get(translated: true))
                            ->listWithLineBreaks()
                            ->alignment(Alignment::End),
                    ])->alignment(Alignment::End),
                ])->from('md')
            ])
            ->recordClasses(function (Part $p) {
                if ($p->part_check->has([CheckType::Error, CheckType::TrackerHold])) {
                    return '!bg-red-300';
                } elseif ($p->part_check->has(CheckType::Warning)) {
                    return '!bg-orange-300';
                }
                return null;
            })
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
                            ->where('part_status', PartStatus::Certified)
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
