<?php

namespace App\Filament\Actions;

use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Events\PartHeaderEdited;
use App\Jobs\UpdateZip;
use App\LDraw\Parse\Parser;
use App\LDraw\PartManager;
use App\Models\Part\Part;
use App\Models\Part\PartCategory;
use App\Models\User;
use App\Rules\PatternKeyword;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EditHeaderAction
{
    public static function make(Part $part, ?string $name = null): EditAction
    {
        return EditAction::make($name)
            ->label('Edit Header')
            ->record($part)
            ->form(self::formSchema($part))
            ->mutateRecordDataUsing(function (array $data) use ($part): array {
                $data['help'] = $part->help()->orderBy('order')->get()->implode('text', "\n");
                $data['keywords'] = implode(', ', $part->keywords->sortBy('keyword')->pluck('keyword')->all());
                $data['history'] = '';
                foreach ($part->history as $h) {
                    $data['history'] .= $h->toString() . "\n";
                }
                return $data;
            })
            ->using(fn (Part $p, array $data) => self::updateHeader($p, $data))
            ->successNotificationTitle('Header updated')
            ->visible(Auth::user()?->can('update', $part) ?? false);
    }

    protected static function formSchema(Part $part): array
    {
        return [
            TextInput::make('description')
                ->extraAttributes(['class' => 'font-mono'])
                ->required()
                ->string()
                ->rules([
                    fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($part) {
                        if (! app(\App\LDraw\Check\PartChecker::class)->checkLibraryApprovedDescription($value)) {
                            $fail('partcheck.description.invalidchars')->translate();
                        }
                        if (
                            $part->type->inPartsFolder() &&
                            $part->category->category !== 'Moved' &&
                            $part->category->category !== 'Sticker' &&
                            $part->category->category !== 'Sticker Shortcut' &&
                            ! app(\App\LDraw\Check\PartChecker::class)->checkDescriptionForPatternText($part->name(), $value)
                        ) {
                            $fail('partcheck.description.patternword')->translate();
                        }
                    }
                ]),
            Select::make('type')
                ->options(PartType::options(PartType::partsFolderTypes()))
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->selectablePlaceholder(false),
            Select::make('type_qualifier')
                ->options(PartTypeQualifier::options())
                ->nullable()
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder()),
            Textarea::make('help')
                ->helperText('Do not include 0 !HELP; each line will be a separate help line')
                ->extraAttributes(['class' => 'font-mono'])
                ->rows(6)
                ->nullable()
                ->string(),
            Select::make('part_category_id')
                ->relationship(
                    name: 'category',
                    titleAttribute: 'category',
                )
                ->helperText('A !CATEGORY meta will be added only if this differs from the first word in the description')
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->searchable()
                ->preload()
                ->selectablePlaceholder(false)
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $part) {
                        if ($part->type->inPartsFolder()) {
                            $c = app(\App\LDraw\Parse\Parser::class)->getDescriptionCategory($get('description'));
                            $cat = PartCategory::firstWhere('category', $c);
                            if (is_null($cat) && is_null($value)) {
                                $fail('partcheck.category.invalid')->translate(['value' => $c]);
                            }
                        }
                    }
                ]),
            Textarea::make('keywords')
                ->helperText('Note: keyword order will not be preserved')
                ->extraAttributes(['class' => 'font-mono'])
                ->rows(3)
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->rules([new PatternKeyword()]),
            TextInput::make('cmdline')
                ->nullable()
                ->extraAttributes(['class' => 'font-mono'])
                ->hidden(!$part->type->inPartsFolder())
                ->disabled(!$part->type->inPartsFolder())
                ->string(),
            TextArea::make('history')
                ->helperText('Must include 0 !HISTORY; ALL changes to existing history must be documented with a comment')
                ->extraAttributes(['class' => 'font-mono'])
                ->rows(6)
                ->nullable()
                ->string()
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $part) {
                        $value = Parser::dos2unix(trim($value));
                        $lines = explode("\n", $value);
                        if ($value !== '' && count($lines) != mb_substr_count($value, '0 !HISTORY')) {
                            $fail('partcheck.history.invalid')->translate();
                            return;
                        }

                        $history = app(Parser::class)->getHistory($value);
                        if (! is_null($history)) {
                            foreach ($history as $hist) {
                                if (is_null(User::fromAuthor($hist['user'])->first())) {
                                    $fail('partcheck.history.author')->translate();
                                }
                            }
                        }

                        $hist = '';
                        foreach ($part->history()->oldest()->get() as $h) {
                            $hist .= $h->toString() . "\n";
                        }
                        $hist = Parser::dos2unix(trim($hist));
                        if (((!empty($hist) && empty($value)) || $hist !== $value) && empty($get('editcomment'))) {
                            $fail('partcheck.history.alter')->translate();
                        }
                    }
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
                $cat = PartCategory::firstWhere('category', $cat);
                if (!is_null($cat) && $part->part_category_id !== $cat->id) {
                    $part->part_category_id = $cat->id;
                }
            }
        }

        if ($part->type->inPartsFolder() &&
            !is_null($data['part_category_id']) &&
            $part->part_category_id !== (int)$data['part_category_id']
        ) {
            $cat = PartCategory::find($data['part_category_id']);
            $changes['old']['category'] = $part->category->category;
            $changes['new']['category'] = $cat->category;
            $part->part_category_id = $cat->id;
        }

        if ($part->type->inPartsFolder() && PartType::tryFrom($data['type']) !== $part->type) {
            $pt = PartType::tryFrom($data['type']);
            $changes['old']['type'] = $part->type->value;
            $changes['new']['type'] = $pt->value;
            $part->type = $pt;
        }

        if (!is_null($data['type_qualifier'] ?? null)) {
            $pq = PartTypeQualifier::tryFrom($data['type_qualifier']);
        } else {
            $pq = null;
        }
        if ($part->type_qualifier !== $pq) {
            $changes['old']['qual'] = $part->type_qualifier->value ?? '';
            $changes['new']['qual'] = $pq->value ?? '';
            $part->type_qualifier = $pq;
        }

        if (!is_null($data['help'] ?? null) && trim($data['help']) !== '') {
            $newHelp = "0 !HELP " . str_replace(["\n","\r"], ["\n0 !HELP ",''], $data['help']);
            $newHelp = $manager->parser->getHelp($newHelp);
        } else {
            $newHelp = [];
        }

        $partHelp = $part->help->pluck('text')->all();
        if ($partHelp !== $newHelp) {
            $changes['old']['help'] = "0 !HELP " . implode("\n0 !HELP ", $partHelp);
            $changes['new']['help'] = "0 !HELP " . implode("\n0 !HELP ", $newHelp);
            $part->setHelp($newHelp);
        }

        if (!array_key_exists('keywords', $data)) {
            $data['keywords'] = [];
        } else {
            $data['keywords'] = array_map(fn(string $kw) => trim($kw), explode(',', str_replace("\n", ",", $data['keywords'])));
        }
        $partKeywords = $part->keywords->pluck('keyword')->all();
        if ($partKeywords !== $data['keywords']) {
            $changes['old']['keywords'] = implode(", ", $partKeywords);
            $changes['new']['keywords'] = implode(", ", $data['keywords']);
            $part->setKeywords($data['keywords']);
        }

        $newHistory = $manager->parser->getHistory($data['history'] ?? '');
        $partHistory = [];
        if ($part->history->count() > 0) {
            foreach ($part->history as $h) {
                $partHistory[] = $h->toString();
            }
        }
        $partHistory = implode("\n", $partHistory);
        if ($manager->parser->getHistory($partHistory) !== $newHistory) {
            $changes['old']['history'] = $partHistory;
            $part->setHistory($newHistory ?? []);
            $part->refresh();
            $changes['new']['history'] = '';
            if ($part->history->count() > 0) {
                foreach ($part->history as $h) {
                    $changes['new']['history'] .= $h->toString() . "\n";
                }
            }
        }

        if ($part->cmdline !== ($data['cmdline'] ?? null)) {
            $changes['old']['cmdline'] = $part->cmdline ?? '';
            $changes['new']['cmdline'] = $data['cmdline'] ?? '';
            $part->cmdline = $data['cmdline'] ?? null;
        }

        if (count($changes['new']) > 0) {
            $part->save();
            $part->refresh();
            $part->generateHeader();
            $manager->checkPart($part);
            // Post an event
            PartHeaderEdited::dispatch($part, Auth::user(), $changes, $data['editcomment'] ?? null);
            Auth::user()->notification_parts()->syncWithoutDetaching([$part->id]);
            UpdateZip::dispatch($part);
        }

        return $part;
    }

}
