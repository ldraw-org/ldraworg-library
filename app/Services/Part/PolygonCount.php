<?php
namespace App\Services\Part;

use App\Models\Part\Part;

/**
 * Counts the type 2-5 lines in a part after flattening its whole subfile
 * hierarchy, so each type 1 reference contributes its child's total once
 * per occurrence.
 *
 * @phpstan-type Counts array{2: int, 3: int, 4: int, 5: int}
 */
final class PolygonCount
{
    /** @var array<string, Counts> keyed by part filename */
    private array $cache = [];

    /** @var array<string, true> filenames currently being expanded (cycle guard) */
    private array $stack = [];

    /**
     * @return Counts
     */
    public function count(Part $part): array
    {
        $key = $part->filename;

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // A cyclic reference is invalid LDraw; don't recurse forever.
        if (isset($this->stack[$key])) {
            return $this->empty();
        }

        $this->stack[$key] = true;
        $counts = $this->empty();

        foreach (preg_split('/\R/', $part->body->body) ?: [] as $line) {
            $line = trim($line);
            $type = $line === '' ? null : $line[0];

            if ($type === '2' || $type === '3' || $type === '4' || $type === '5') {
                $counts[(int) $type]++;
            } elseif ($type === '1') {
                $child = $this->resolveSubfile($line);
                if ($child !== null) {
                    foreach ($this->count($child) as $t => $n) {
                        $counts[$t] += $n;
                    }
                }
            }
        }

        unset($this->stack[$key]);

        return $this->cache[$key] = $counts;
    }

    /**
     * Triangles, with each quad counted as two.
     */
    public function triangleCount(Part $part): int
    {
        $counts = $this->count($part);

        return $counts[3] + 2 * $counts[4];
    }

    /**
     * A type 1 line is: 1 <colour> x y z a b c d e f g h i <file>
     * The filename is the 15th token and may contain spaces.
     */
    private function resolveSubfile(string $line): ?Part
    {
        $tokens = preg_split('/\s+/', $line, 15);

        if ($tokens === false || count($tokens) < 15) {
            return null;
        }

        $name = str_replace('\\', '/', strtolower(trim($tokens[14])));

        // ASSUMPTION: Part::$filename holds the library path, e.g. "parts/s/3001s01.dat" or "p/stud.dat"
        return Part::query()
            ->with('body')
            ->whereIn('filename', ["parts/{$name}", "p/{$name}"])
            ->first();
    }

    /**
     * @return Counts
     */
    private function empty(): array
    {
        return [2 => 0, 3 => 0, 4 => 0, 5 => 0];
    }
}
