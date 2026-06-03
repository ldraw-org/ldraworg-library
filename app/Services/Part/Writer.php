<?php

namespace App\Services\Part;

use App\Models\Part\Part;
use App\Services\BackupFile;

class Writer
{
    public function createOrUpdate(array $values, string $bodyText, array $keywords = [], array $history = []): Part
    {
        $upart = Part::unofficial()->firstWhere('filename', $values['filename']);
        $opart = Part::official()->firstWhere('filename', $values['filename']);
        if ($upart) {
            app(BackupFile::class)->handle(str_replace('/', '-', $upart->filename), $upart->get());
            $upart->votes()->delete();
            $upart->fill($values);
        } elseif ($opart) {
            $upart = Part::create($values);
            $opart->unofficial_part()->associate($upart);
            $opart->save();
        } else {
            $upart = Part::create($values);
        }

        $upart->setKeywords($keywords);
        $upart->setHistory($history);
        $upart->setBodyQuietly($bodyText);
        $upart->save();

        return $upart->refresh();    }
}
