<?php

namespace App\Filament\Tables\Columns;

use App\Models\Part\Part;
use Filament\Tables\Columns\Column;

class PartStatusColumn extends Column
{
    protected string $view = 'filament.tables.columns.part-status-column';

    public function hasPart(): bool
    {
        return !is_null($this->getPart());
    }

    public function getPart(): ?Part
    {
        if ($this->getRecord() instanceof Part) {
            return $this->getRecord();
        }
        return $this->getRecord()?->part;
    }
}
