<?php

namespace App\Filament\Actions;

use App\Enums\PartCategory;
use App\Events\PartSubmitted;
use App\Services\Part\PartMover;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use App\Enums\PartType;
use App\Events\PartRenamed;
use App\Models\Part\Part;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;

class EditNumberAction extends EditAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Renumber/Move')
            ->modalHeading('Move/Renumber Part')
            ->successNotificationTitle('Renumber/Move Successful')
            ->failureNotificationTitle('No rename specified')
            ->visible(fn (?Part $record) => Auth::user()?->can('move', $record))
            ->schema(fn (?Part $record) => $this->getRenumberFormSchema($record))
            ->using(fn (array $data, EditAction $action, PartMover $mover) => $this->handleMove($data, $action, $mover));
    }

    protected function getRenumberFormSchema(Part $part): array
    {
        return [
            TextInput::make('folder')
                ->label('Current Location')
                ->placeholder($part->type->folder())
                ->disabled()
                ->dehydrated(false)
                ->visible($part->isUnofficial()),
            TextInput::make('name')
                ->label('Current Name')
                ->placeholder(basename($part->filename))
                ->disabled()
                ->dehydrated(false),
            Select::make('type')
                ->label('New Type:')
                ->required()
                ->placeholder(false)
                ->live()
                ->options($part->type->isDatFormat() ? PartType::options(PartType::datFormat()) : PartType::options(PartType::imageFormat()))
                ->visible($part->isUnofficial()),
            TextInput::make('newname')
                ->label($part->isUnofficial() ? 'New Name' : 'Move to')
                ->helperText($part->isUnofficial() ? 'Exclude the folder from the name' : '')
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $part) {
                        $type = PartType::tryFrom($get('type')) ?? $part->type;
                        $input = filled($value) ? $value : $part->filename;

                        $sanitizedName = pathinfo(basename($input), PATHINFO_FILENAME);

                        if (blank($sanitizedName)) {
                            $sanitizedName = pathinfo(basename($part->filename), PATHINFO_FILENAME);
                        }

                        $finalFilename = "{$sanitizedName}.{$type->format()}";

                        $isActuallyChanging = ($type->value !== $part->type->value) ||
                            ("{$sanitizedName}.{$type->format()}" !== basename($part->filename));
                        if (!$isActuallyChanging) {
                            $fail('No change detected.');
                        }
                        if (Part::where('filename', $finalFilename)->exists()) {
                            $fail('A part of this name already exists,');
                        }
                    }
                ]),
            ];
    }

    protected function handleMove(array $data, EditAction $action, PartMover $mover): void
    {
        /** @var Part $part */
        $part = $this->getRecord();
        $type = PartType::tryFrom($data['type'] ?? null) ?? $part->type;

        $input = filled($data['newname']) ? $data['newname'] : $part->filename;

        $sanitizedName = pathinfo(basename($input), PATHINFO_FILENAME);
        if (blank($sanitizedName)) {
            $sanitizedName = pathinfo(basename($part->filename), PATHINFO_FILENAME);
        }

        $finalFilename = "{$sanitizedName}.{$type->format()}";

        $isActuallyChanging = ($type->value !== $part->type->value) ||
            ("{$sanitizedName}.{$type->format()}" !== basename($part->filename));

        if (!$isActuallyChanging) {
            $action->cancel();
        }

        if ($part->isUnofficial()) {
            $oldname = $part->filename;
            $mover->moveUnofficialPart($part, $type, $finalFilename);
            PartRenamed::dispatch($part, Auth::user(), $oldname, $part->filename);
        } else {
            $upart = $mover->moveOfficialPart($part, $finalFilename, Auth::user());
            PartSubmitted::dispatch($upart, Auth::user());
            $mpart = $upart->parents()->firstWhere('category', PartCategory::Moved);
            PartSubmitted::dispatch($mpart, Auth::user());
        }
    }
}
