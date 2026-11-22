<?php

namespace App\Services\OmrModel;

use App\Models\Omr\OmrModel;
use App\Models\Part\Part;
use App\Services\LDraw\LDrawFile;
use App\Services\Omr\Set\Writer as SetWriter;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Str;

/**
 * Inspects an uploaded OMR model file and works out the form defaults the uploader would otherwise fill in by hand.
 */
class UploadChecker
{
    public function __construct(
        protected SetWriter $setWriter,
    ) {
    }

    /**
     * @return array{
     *     errors: list<string>,
     *     warnings: list<string>,
     *     set_id: ?int,
     *     user_id: ?int,
     *     alt_model: bool,
     *     missing_parts: bool
     * }
     */
    public function check(LDrawFile $file): array
    {
        $result = [
            'errors' => [],
            'warnings' => [],
            'set_id' => null,
            'user_id' => null,
            'alt_model' => false,
            'missing_parts' => false,
        ];

        if ($file->mimetype !== 'text/plain') {
            $result['errors'][] = 'The file is not an LDraw text file';
            return $result;
        }

        $model = new ParsedPartCollection($file->contents);
        $submodels = $this->submodelNames($file->contents);
        $partNames = collect($model->subparts() ?? [])
            ->map(fn (string $name): string => str_replace('\\', '/', $name))
            ->reject(fn (string $name): bool => in_array($name, $submodels, true));

        if ($partNames->isEmpty()) {
            $result['errors'][] = 'The file does not reference any parts';
            return $result;
        }

        $setNumber = $this->setNumber($file->filename) ?? $this->setNumber($submodels[0] ?? '');
        $set = is_null($setNumber) ? null : $this->setWriter->findFromSetNumber($setNumber, createIfMissing: true);
        if (is_null($setNumber)) {
            $result['warnings'][] = 'A set number could not be found in the file name';
        } elseif (is_null($set)) {
            $result['warnings'][] = "Set {$setNumber} was not found at Rebrickable, add it from the set field";
        } else {
            $result['set_id'] = $set->id;
            if (OmrModel::where('set_id', $set->id)->whereNull('alt_model_name')->exists()) {
                $result['alt_model'] = true;
                $result['warnings'][] = "Set {$setNumber} already has a main model, this will be added as an alternate model";
            }
        }

        $result['user_id'] = $model->authorUser()?->id;
        if (is_null($result['user_id'])) {
            $result['warnings'][] = 'The author in the file header does not match a library user';
        }

        $missing = $this->partsNotInOfficialLibrary($partNames->values()->all());
        if ($missing !== []) {
            $result['missing_parts'] = true;
            $result['warnings'][] = 'Parts not in the official library: ' . implode(', ', $missing);
        }

        return $result;
    }

    /**
     * The set number at the start of an OMR file name ("6399-1 - Airport Shuttle.mpd"), defaulting to the first version.
     */
    public function setNumber(string $filename): ?string
    {
        if (!preg_match('/^(\d{3,7})(-\d+)?/', basename($filename), $match)) {
            return null;
        }

        return $match[1] . ($match[2] ?? '-1');
    }

    /**
     * @return list<string>
     */
    protected function submodelNames(string $contents): array
    {
        preg_match_all('/^\h*0\h+FILE\h+(.+?)\h*$/mi', $contents, $matches);

        return collect($matches[1])
            ->map(fn (string $name): string => Str::of($name)->trim()->lower()->replace('\\', '/')->toString())
            ->all();
    }

    /**
     * @param list<string> $partNames
     * @return list<string>
     */
    protected function partsNotInOfficialLibrary(array $partNames): array
    {
        $candidates = collect($partNames)
            ->flatMap(fn (string $name): array => Str::endsWith($name, '.png')
                ? ["parts/textures/{$name}", "p/textures/{$name}"]
                : ["parts/{$name}", "p/{$name}"]);

        $found = Part::query()
            ->official()
            ->whereIn('filename', $candidates)
            ->pluck('filename')
            ->map(fn (string $filename): string => Str::chopStart($filename, ['parts/textures/', 'p/textures/', 'parts/', 'p/']))
            ->all();

        return array_values(array_diff($partNames, $found));
    }
}
