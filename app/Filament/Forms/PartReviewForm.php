<?php

namespace App\Filament\Forms;

use App\Enums\VoteType;
use App\Models\Part\Part;
use App\Models\Vote;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;

class PartReviewForm
{
    public static function make(): array
    {
        return [
            Section::make('Comment / Vote')
                ->schema([
                    Radio::make('vote_type_code')
                        ->label('')
                        ->options(fn (?Part $record) => static::availableVoteTypes($record))
                        ->default('M')
                        ->required()
                        ->markAsRequired(false)
                        ->enum(VoteType::class)
                        ->inline()
                        ->inlineLabel(false)
                        ->validationAttribute('vote type'),
                    Textarea::make('comment')
                        ->rows(5)
                        ->string()
                        ->nullable()
                        ->requiredIf('vote_type_code', ['M', 'H'])
                        ->extraAttributes(['class' => 'font-mono'])
                        ->validationMessages([
                            'required_if' => 'A comment is required',
                        ]),
                ]),
        ];
    }

    private static function availableVoteTypes(?Part $record): array
    {
        if ($record === null) {
            return [];
        }
        $user = Auth::user();
        $options = [];

        foreach (VoteType::cases() as $vt) {
            if ($user?->can('vote', [Vote::class, $record, $vt])) {
                $options[$vt->value] = $vt->label();
            }
        }

        return $options;
    }
}
