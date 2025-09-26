<?php

namespace App\Livewire\Omr\OmrModel;

use App\Filament\Forms\Components\AuthorSelect;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use App\LDraw\LDrawModelMaker;
use App\LDraw\Managers\OmrModelManager;
use App\LDraw\Parse\Parser;
use App\LDraw\Rebrickable;
use App\Models\Mybb\MybbAttachment;
use App\Models\Omr\OmrModel;
use App\Models\Omr\Set;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Add extends Component implements HasSchemas, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public ?array $files = [];
    public array $parts = [];

    public function mount(): void
    {
        $this->authorize('create', OmrModel::class);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(MybbAttachment::with('post', 'user')->omrFiles())
            ->defaultSort('dateuploaded', 'desc')
            ->columns([
                TextColumn::make('dateuploaded')
                    ->sortable()
                    ->label('Date Uploaded')
                    ->dateTime(),
                TextColumn::make('filename')
                    ->sortable(),
                TextColumn::make('post.subject')
                    ->wrap()
                    ->url(fn (MybbAttachment $file): string => $file->post->url()),
                TextColumn::make('user')
                    ->state(fn (MybbAttachment $file): string => $file->user->library_user?->author_string ?? "{$file->user->loginname} - {$file->user->username}"),
                IconColumn::make('set-in-omr')
                    ->boolean()
                    ->state(fn (MybbAttachment $file): bool => !is_null($this->getSetFromFilename($file->filename)))
                    ->url(function (MybbAttachment $file): ?string {
                        $set = $this->getSetFromFilename($file->filename);
                        if (!is_null($set)) {
                            return route('omr.sets.show', $set);
                        }
                        return null;
                    }),
                ToggleColumn::make('posthash')
                    ->label('File Reviewed'),
            ])
            ->recordActions([
                Action::make('view')
                    ->action(function (MybbAttachment $file) {
                        $this->parts = app(LDrawModelMaker::class)->webGl($file->get());
                        $this->dispatch('open-modal', id: 'ldbi');
                    }),
                $this->AddAction(),

            ])
            ->filters([
                Filter::make('unchecked-posts')
                    ->query(fn (Builder $query): Builder => $query->where('posthash', '!=', 1))
            ])
            ->recordClasses(fn (MybbAttachment $file): string => is_null($file->user->library_user) ? '!bg-yellow-300' : '');
    }

    protected function AddAction(): Action
    {
        return Action::make('add')
            ->fillForm(fn (MybbAttachment $file): array => [
                'set_id' => $this->getSetFromFilename($file->filename)?->id,
                'user_id' => User::firstWhere('realname', Arr::get(app(Parser::class)->getAuthor($file->get()), 'realname'))?->id ?? $file->user->library_user?->id,
            ])
            ->schema([
                AuthorSelect::make(),
                Select::make('set_id')
                    ->options(Set::pluck('number', 'id'))
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('number')
                            ->required()
                            ->rules([
                                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                                    if (!Str::endsWith($value, ['-1', '-2'])) {
                                        $value .= '-1';
                                    }
                                    $set = Set::firstWhere('number', $value);
                                    if (!is_null($set)) {
                                        $fail('Set already exists');
                                        return;
                                    }
                                    if ((new Rebrickable())->getSet($value)->isEmpty()) {
                                        $fail('Set number not found at Rebrickable');
                                    }
                                },
                            ]),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        if (!Str::endsWith($data['number'], ['-1', '-2'])) {
                            $data['number'] .= '-1';
                        }
                        $rb_set_data = (new Rebrickable())->getSet($data['number'])->all();
                        return Set::create([
                            'number' => Arr::get($rb_set_data, 'set_num'),
                            'name' => Arr::get($rb_set_data, 'name'),
                            'year' => Arr::get($rb_set_data, 'year'),
                            'rb_url' => Arr::get($rb_set_data, 'set_url'),
                            'theme_id' => Arr::get($rb_set_data, 'theme_id'),
                        ])->id;
                    }),
                Toggle::make('alt_model')
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            if (!$value && OmrModel::where('set_id', $get('set_id'))->whereNull('alt_model_name')->exists()) {
                                $fail('There is already a main model for this set');
                            }
                        }
                    ])
                    ->live(),
                TextInput::make('alt_model_name')
                    ->requiredIfAccepted('alt_model')
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            if (OmrModel::where('set_id', $get('set_id'))->where('alt_model_name', $value)->exists()) {
                                $fail('There is already an alt model with that name');
                            }
                        }
                    ])
                    ->disabled(fn (Get $get): bool => !$get('alt_model'))
                    ->live(),
                Toggle::make('missing_parts'),
                Toggle::make('missing_patterns'),
                Toggle::make('missing_stickers'),
                Textarea::make('notes')
                    ->rows(4)
            ])
            ->action(function (MybbAttachment $file, array $data) {
                app(OmrModelManager::class)->addModelFromMybbAttachment($file, Set::firstWhere('id', $data['set_id']), $data);
            });
    }

    protected function getSetFromFilename(string $filename): ?Set
    {
        $m = preg_match('/\d{3,6}/iu', $filename, $num);
        if ($m) {
            return  Set::where(fn (Builder $query) => $query->orWhere('number', $num[0])->orWhere('number', "{$num[0]}-1"))->first();
        }
        return null;
    }

    #[Layout('components.layout.omr')]
    public function render()
    {
        return view('livewire.omr.omr-model.add');
    }
}
