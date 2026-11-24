<?php

namespace App\Services\Part\Release;

use App\Models\Part\PartRelease;

class ReleaseNotes
{
    /**
     * Build the NoteXXXXCA.txt text for a release.
     *
     * @param array{
     *     total: int,
     *     new: int,
     *     new_of_type: array<string, int>,
     *     moved: list<array{name: string, moved_to: string}>,
     *     renamed: list<array{name: string, description: string, old_description: string}>,
     *     fixed: list<array{name: string, description: string}>,
     *     minor_edits: int
     * } $statistics
     */
    public function make(PartRelease $release, array $statistics): string
    {
        $notes = "ldraw.org Parts Update {$release->name}\n" .
            str_repeat('-', 76) . "\n\n" .
            "Redistributable Parts Library - Core Library\n" .
            str_repeat('-', 76) . "\n\n" .
            'Notes created ' . $release->created_at->format('r') . " by the Parts Tracker\n\n" .
            "Release statistics:\n" .
            "   Total files: {$statistics['total']}\n" .
            "   New files: {$statistics['new']}\n";

        foreach ($statistics['new_of_type'] as $type => $count) {
            if ($count > 0) {
                $notes .= "   New {$type}s: {$count}\n";
            }
        }

        $notes .= "\nMoved Parts\n";
        foreach ($statistics['moved'] as $part) {
            $notes .= $this->line($part['name'], $part['moved_to']);
        }

        $notes .= "\nRenamed Parts\n";
        foreach ($statistics['renamed'] as $part) {
            $notes .= $this->line($part['name'], $part['old_description']) .
                "   changed to    {$part['description']}\n";
        }

        if ($statistics['fixed'] !== []) {
            $notes .= "\nOther Fixed Parts\n";
            foreach ($statistics['fixed'] as $part) {
                $notes .= $this->line($part['name'], $part['description']);
            }
        }

        $notes .= "\nMinor Edits\n" .
            "   {$statistics['minor_edits']} Parts with minor administrative edits and/or license changes\n";

        return $notes;
    }

    protected function line(string $name, string $text): string
    {
        return "   {$name}" . str_repeat(' ', max(27 - strlen($name), 0)) . "{$text}\n";
    }
}
