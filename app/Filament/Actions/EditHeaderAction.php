<?php

namespace App\Filament\Actions;

use App\Models\User;
use Filament\Schemas\Components\Utilities\Get;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Filament\Forms\Components\PreviewSelect;
use App\Models\Part\Part;
use App\Rules\HistoryEditIsValid;
use App\Rules\PatternHasSet;
use App\Services\Check\PartChecker;
use App\Services\Check\PartChecks\LibraryApprovedDescription;
use App\Services\Check\PartChecks\PatternPartDescription;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\Part\HeaderEdit;

class EditHeaderAction extends EditAction
{
    public function setUp(): void
    {
        parent::setUp();

        $headerEditor = app(HeaderEdit::class);

        $this->label('Edit Header')
            ->schema(fn() => $this->formSchema())
            ->mutateRecordDataUsing(fn (?Part $record, array $data) => $headerEditor->setupHeaderData($record, $data))
            ->using(fn (?Part $record, array $data): Part => $headerEditor->storeHeaderData($record, $data))
            ->after(function () {
                $this->getLivewire()->part?->refresh();
            })
            ->successNotificationTitle('Header updated')
            ->visible(fn (?Part $record) => $record?->isUnofficial() && (Auth::user()?->can('update', $record) ?? false));
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('description')
                ->extraAttributes(['class' => 'font-mono'])
                ->required()
                ->string()
                ->rules([
                    fn (Part $p, Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($p, $get) {
                        $check_part = [
                            'description' => "0 {$value}",
                            'name' => "0 Name: {$p->metaName}",
                            'type' => PartType::tryFrom($get('type'))?->ldrawString(true),
                            'category' => PartCategory::tryFrom($get('category') ?? '')?->ldrawString(),
                            'keywords' => '0 !KEYWORDS ' . collect(explode(',', Str::of($get('keywords'))->trim()->squish()->replace(["/n", ', ',' ,'], ',')->toString()))->filter()->implode(', ')
                        ];
                        $check_part = new ParsedPartCollection(implode("\n", $check_part));
                        $errors = app(PartChecker::class)->runSingle(LibraryApprovedDescription::class, $check_part);
                        if ($errors->hasErrors()) {
                            $fail($errors->first()->message());
                            return;
                        }
                        $errors = app(PartChecker::class)->runSingle(PatternPartDescription::class, $check_part);
                        if ($errors->hasErrors()) {
                            $fail($errors->first()->message());
                        }
                    }
                ]),
            Select::make('type')
                ->options(PartType::options(PartType::partsFolderTypes()))
                ->hidden(fn (Part $p) => !$p->type->inPartsFolder())
                ->disabled(fn (Part $p) => !$p->type->inPartsFolder())
                ->selectablePlaceholder(false)
                ->in(PartType::partsFolderTypes()),
            Select::make('type_qualifier')
                ->options(PartTypeQualifier::options())
                ->nullable()
                ->hidden(fn (Part $p) => !$p->type->inPartsFolder())
                ->disabled(fn (Part $p) => !$p->type->inPartsFolder())
                ->in(PartTypeQualifier::cases()),
            Textarea::make('help')
                ->extraAttributes(['class' => 'font-mono'])
                ->string()
                ->rows(4)
                ->nullable(),
            Select::make('category')
                ->options(PartCategory::options())
                ->helperText('A !CATEGORY meta will be added only if this differs from the first word in the description')
                ->hidden(fn (Part $p) => !$p->type->inPartsFolder())
                ->disabled(fn (Part $p) => !$p->type->inPartsFolder())
                ->searchable()
                ->preload()
                ->selectablePlaceholder(false)
                ->in(PartCategory::cases()),
            Textarea::make('keywords')
                ->helperText(
                    fn (Part $p) =>
                    'Note: keyword order' .
                    (!is_null($p->rebrickable_part) ? ' and external site keywords' : '') .
                    ' will not be preserved'
                )
                ->extraAttributes(['class' => 'font-mono'])
                ->nullable()
                ->string()
                ->rows(3)
                ->hidden(fn (Part $p) => !$p->type->inPartsFolder())
                ->disabled(fn (Part $p) => !$p->type->inPartsFolder())
                ->rules([
                    new PatternHasSet(),
                ]),
            TextInput::make('cmdline')
                ->nullable()
                ->extraAttributes(['class' => 'font-mono'])
                ->hidden(fn (Part $p) => !$p->type->inPartsFolder())
                ->disabled(fn (Part $p) => !$p->type->inPartsFolder())
                ->string(),
            PreviewSelect::make(),
            Repeater::make('history')
                ->table([
                    TableColumn::make('Date')
                        ->width('18%'),
                    TableColumn::make('Author')
                        ->width('30%'),
                    TableColumn::make('Comment'),
                ])
                ->schema([
                    DatePicker::make('created_at')
                        ->displayFormat('Y-m-d')
                        ->label('Date')
                        ->rules([
                           Rule::date(),
                        ])
                        ->grow(false)
                        ->required(),
                    Select::make('user_id')
                        ->options(User::pluck('author_string', 'id')->all())
                        ->rules(['exists:App\Models\User,id'])
                        ->searchable()
                        ->placeholder(false)
                        ->required(),
                    TextInput::make('comment')
                        ->extraAttributes(['class' => 'font-mono'])
                        ->required()
                        ->string(),
                ])
                ->extraAttributes(['class' => 'font-mono'])
                ->compact()
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
}
