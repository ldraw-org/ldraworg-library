<?php

namespace App\Filament\Actions;

use Filament\Schemas\Components\Utilities\Get;
use App\LDraw\Check\Checks\LibraryApprovedDescription;
use App\LDraw\Check\Checks\PatternPartDesciption;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Events\PartHeaderEdited;
use App\Filament\Forms\Components\AuthorSelect;
use App\Filament\Forms\Components\PreviewSelect;
use App\Jobs\UpdateRebrickable;
use App\Jobs\UpdateZip;
use App\LDraw\Check\PartChecker;
use App\LDraw\Parse\ParsedPart;
use App\LDraw\PartManager;
use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Models\Part\PartKeyword;
use App\Models\User;
use App\Rules\HistoryEditIsValid;
use App\Rules\PatternHasSet;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EditHeaderAction
{
    public static function make(Part $part, ?string $name = null): EditAction
    {
        return EditAction::make($name)
            ->label('Edit Header')
            ->record($part)
            ->schema(self::formSchema($part))
            ->mutateRecordDataUsing(function (array $data) use ($part): array {
                $data['help'] = implode("\n", $part->help ?? []);
                if (is_null($part->getRebrickablePart())) {
                    $kws = $part->keywords;
                } else {
                    $kws = $part->keywords
                        ->reject(function (PartKeyword $kw) {
                            return Str::of($kw->keyword)->lower()->startsWith('rebrickable') ||
                            Str::of($kw->keyword)->lower()->startsWith('brickset') ||
                            Str::of($kw->keyword)->lower()->startsWith('brickowl') ||
                            Str::of($kw->keyword)->lower()->startsWith('bricklink');
                        });
                }
                $data['keywords'] = $kws->sortBy('keyword')->implode('keyword', ', ');
                $preview = $part->previewValues();
                $data['preview_rotation'] = $preview['rotation'];
                $data['history'] = $part->history->sortBy('created_at')->map->only('created_at', 'user_id', 'comment')->all();
                return $data;
            })
            ->using(fn (Part $p, array $data): Part => self::updateHeader($p, $data))
            ->successNotificationTitle('Header updated')
            ->visible($part->isUnofficial() && Auth::user()?->can('update', $part) ?? false);
    }

    protected static function formSchema(Part $part): array
    {
        return [
            TextInput::make('description')
                ->extraAttributes(['class' => 'font-mono'])
                ->required()
                ->string()
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($part, $get) {
                        $p = ParsedPart::fromPart($part);
                        $p->description = $value;
                        $p->keywords = collect(explode(',', Str::of($get('keywords'))->trim()->squish()->replace(["/n", ', ',' ,'], ',')->toString()))->filter()->all();
                        $errors = (new PartChecker($p))->singleCheck(new LibraryApprovedDescription());
                        if ($errors) {
                            $fail($errors[0]);
                            return;
                        }
                        $errors = (new PartChecker($p))->singleCheck(new PatternPartDesciption());
                        if ($errors) {
                            $fail($errors[0]);
                        }
                    }
                ]),
            Select::make('type')
                ->options(PartType::options(PartType::partsFolderTypes()))
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->selectablePlaceholder(false)
                ->in(PartType::partsFolderTypes()),
            Select::make('type_qualifier')
                ->options(PartTypeQualifier::options())
                ->nullable()
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->in(PartTypeQualifier::cases()),
            Textarea::make('help')
                ->extraAttributes(['class' => 'font-mono'])
                ->string()
                ->rows(4)
                ->nullable(),
            Select::make('category')
                ->options(PartCategory::options())
                ->helperText('A !CATEGORY meta will be added only if this differs from the first word in the description')
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->searchable()
                ->preload()
                ->selectablePlaceholder(false)
                ->in(PartCategory::cases()),
                Textarea::make('keywords')
                ->helperText(
                    fn (Part $p) =>
                    'Note: keyword order' .
                    (!is_null($part->rebrickable_part) ? ' and external site keywords' : '') .
                    ' will not be preserved'
                )
                ->extraAttributes(['class' => 'font-mono'])
                ->nullable()
                ->string()
                ->rows(3)
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->rules([
                    new PatternHasSet(),
                ]),
            TextInput::make('cmdline')
                ->nullable()
                ->extraAttributes(['class' => 'font-mono'])
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->string(),
            PreviewSelect::make(),
            Repeater::make('history')
                ->schema([
                    DatePicker::make('created_at')
                        ->native(false)
                        ->extraAttributes(['class' => 'font-mono'])
                        ->displayFormat('Y-m-d')
                        ->label('Date')
                        ->rules([
                           Rule::date(),
                        ])
                        ->required()
                        ->live(),
                    AuthorSelect::make()
                        ->required()
                        ->live(),
                    TextInput::make('comment')
                        ->extraAttributes(['class' => 'font-mono'])
                        ->columnSpanFull()
                        ->required()
                        ->string()
                        ->live()
                ])
                ->columns(2)
                ->collapsed()
                ->extraAttributes(['class' => 'font-mono'])
                ->itemLabel(fn (array $state): ?string => (new Carbon(Arr::get($state, 'created_at')))->toDateString() . ' ' . (User::find(Arr::get($state, 'user_id'))?->historyString() ?? '') .' ' . Arr::get($state, 'comment'))
                ->helperText('ALL changes to existing history must be documented with a comment')
                ->reorderable(false)
                ->rules([
                    new HistoryEditIsValid(),
                ]),
            TextArea::make('editcomment')
                ->label('Comment')
                ->extraAttributes(['class' => 'font-mono'])
                ->rows(3)
                ->nullable()
                ->string()
            ];
    }

    protected static function updateHeader(Part $part, array $data): Part
    {
        $manager = app(PartManager::class);
        $changes = ['old' => [], 'new' => []];
        if ($data['description'] !== $part->description) {
            $changes['old']['description'] = $part->description;
            $changes['new']['description'] = $data['description'];
            $part->description = $data['description'];
            if ($part->type->inPartsFolder()) {
                $cat = $manager->parser->getDescriptionCategory($part->description);
                if (!is_null($cat) && $part->category !== $cat) {
                    $part->category = $cat;
                }
            }
        }
        if ($part->type->inPartsFolder() &&
            Arr::has($data, 'category') &&
            $part->category !== PartCategory::tryFrom($data['category'])
        ) {
            $cat = PartCategory::tryFrom($data['category']);
            $changes['old']['category'] = $part->category->value;
            $changes['new']['category'] = $cat->value;
            $part->category = $cat;
        }

        if ($part->type->inPartsFolder() && Arr::has($data, 'type') && PartType::tryFrom($data['type']) !== $part->type) {
            $pt = PartType::tryFrom($data['type']);
            $changes['old']['type'] = $part->type->value;
            $changes['new']['type'] = $pt->value;
            $part->type = $pt;
        }

        if (Arr::has($data, 'type_qualifier')) {
            $pq = PartTypeQualifier::tryFrom($data['type_qualifier']);
        } else {
            $pq = null;
        }
        if ($part->type_qualifier !== $pq) {
            $changes['old']['qual'] = $part->type_qualifier->value ?? '';
            $changes['new']['qual'] = $pq->value ?? '';
            $part->type_qualifier = $pq;
        }

        if (Arr::has($data, 'help')) {
            $newHelp = "0 !HELP " . str_replace(["\n","\r"], ["\n0 !HELP ",''], $data['help']);
            $newHelp = $manager->parser->getHelp($newHelp);
        } else {
            $newHelp = [];
        }

        if ($part->help !== $newHelp) {
            $changes['old']['help'] = "0 !HELP " . implode("\n0 !HELP ", $part->help);
            $changes['new']['help'] = "0 !HELP " . implode("\n0 !HELP ", $newHelp);
            $part->help = $newHelp;
        }

        if (!Arr::has($data, 'keywords')) {
            $new_kws = collect([]);
        } else {
            $new_kws = collect(explode(',', Str::of($data['keywords'])->trim()->squish()->replace(["\n", ', ',' ,'], ',')->toString()))->filter();
        }
        if (!is_null($part->getRebrickablePart())) {
            $extKeywords = collect($part->keywords
                ->filter(function (PartKeyword $kw) {
                    return Str::of($kw->keyword)->lower()->startsWith('rebrickable') ||
                    Str::of($kw->keyword)->lower()->startsWith('brickset') ||
                    Str::of($kw->keyword)->lower()->startsWith('brickowl') ||
                    Str::of($kw->keyword)->lower()->startsWith('bricklink');
                })
                ->pluck('keyword')
                ->all());
            $new_kws = $new_kws
                ->reject(function (string $kw) {
                    return Str::of($kw)->lower()->startsWith('rebrickable') ||
                    Str::of($kw)->lower()->startsWith('brickset') ||
                    Str::of($kw)->lower()->startsWith('brickowl') ||
                    Str::of($kw)->lower()->startsWith('bricklink');
                })
                ->merge($extKeywords);
        }
        $partKeywords = collect($part->keywords->pluck('keyword')->all());
        if ($partKeywords->diff($new_kws)->isNotEmpty() || $new_kws->diff($partKeywords)->isNotEmpty()) {
            $changes['old']['keywords'] = implode(", ", $partKeywords->all());
            $changes['new']['keywords'] = implode(", ", $new_kws->all());
            $part->setKeywords($new_kws->all());
            UpdateRebrickable::dispatch($part);
        }

        $old_hist = collect($part->history->sortBy('created_at')->map(fn (PartHistory $h) => $h->toString()));
        $new_hist = collect($data['history'])
            ->map(
                fn (array $state) =>
                '0 !HISTORY ' .
                (new Carbon(Arr::get($state, 'created_at')))->toDateString() .
                ' ' .
                (User::find(Arr::get($state, 'user_id'))?->historyString() ?? '') .
                ' ' .
                Str::of(Arr::get($state, 'comment'))->squish()->trim()->toString()
            );
        if ($new_hist->diff($old_hist)->all()) {
            $changes['old']['history'] = $old_hist->implode("\n");
            $part->setHistory($manager->parser->parse($new_hist->implode("\n"))->history ?? []);
            $part->load('history');
            $changes['new']['history'] = collect($part->history->sortBy('created_at')->map(fn (PartHistory $h) => $h->toString()))->implode("\n");
        }

        if ($part->cmdline !== Arr::get($data, 'cmdline')) {
            $changes['old']['cmdline'] = $part->cmdline ?? '';
            $changes['new']['cmdline'] = Arr::get($data, 'cmdline', '');
            $part->cmdline = Arr::get($data, 'cmdline');
        }

        $preview = '16 0 0 0 ' . Str::of(Arr::get($data, 'preview_rotation'))->squish();
        $preview = $preview == '16 0 0 0 1 0 0 0 1 0 0 0 1' ? null : $preview;

        $preview_changed = false;
        if ($part->preview !== $preview) {
            $changes['old']['preview'] = $part->preview ?? '16 0 0 0 1 0 0 0 1 0 0 0 1';
            $changes['new']['preview'] = $preview ?? '16 0 0 0 1 0 0 0 1 0 0 0 1';
            $part->preview = $preview;
            $preview_changed = true;
        }

        if (count($changes['new']) > 0) {
            $part->save();
            $part->refresh();
            $part->generateHeader();
            if ($preview_changed) {
                $manager->updateImage($part);
            }
            $manager->checkPart($part);
            // Post an event
            PartHeaderEdited::dispatch($part, Auth::user(), $changes, $data['editcomment'] ?? null);
            Auth::user()->notification_parts()->syncWithoutDetaching([$part->id]);
            UpdateZip::dispatch($part);
        }

        return $part;
    }
}
