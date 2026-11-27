<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\BackupFile;
use Illuminate\Support\Str;

class Writer
{
    public function __construct(
        protected GenerateHeader $generateHeader,
    ) {
    }

    public function createOrUpdate(array $values, string $bodyText, array $keywords = [], array $history = []): Part
    {
        $upart = Part::unofficial()->firstWhere('filename', $values['filename']);
        $opart = Part::official()->firstWhere('filename', $values['filename']);
        if ($upart) {
            app(BackupFile::class)->handle(str_replace('/', '-', $upart->filename), $upart->get());
            $upart->votes()->delete();
            $upart->fill($values);
        } elseif ($opart) {
            $upart = $this->create($values);
            $opart->unofficial_part()->associate($upart);
            $opart->save();
        } else {
            $upart = $this->create($values);
        }

        // normalize filename to all lowercase
        $upart->filename = Str::lower($upart->filename);

        $upart->setKeywords($keywords);
        $upart->setHistory($history);
        $upart->setBodyQuietly($bodyText);
        $upart->save();
        $this->generateHeader->updatePartHeader($upart);

        return $upart->refresh();
    }

    /**
     * Creates the part with a placeholder header, which createOrUpdate() then generates.
     */
    protected function create(array $values): Part
    {
        return Part::create(['header' => ''] + $values);
    }
}
