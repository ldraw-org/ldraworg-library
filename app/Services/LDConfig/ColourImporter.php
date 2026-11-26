<?php

namespace App\Services\LDConfig;

use App\Enums\RebrickableCsvFile;
use App\Models\LdrawColour;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;
use App\Services\External\RebrickableCsv;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ColourImporter
{
    public function __construct(
        protected LDConfigFile $ldconfig,
        protected RebrickableCsv $rebrickableCsv,
        protected CacheService $cache,
    ) {
    }

    /**
     * Does nothing without an LDConfig. Without the Rebrickable CSV downloads, stored Rebrickable
     * mappings are kept and new colours are left unmapped.
     */
    public function import(): void
    {
        if (!$this->ldconfig->exists()) {
            return;
        }

        $rbColors = $this->rebrickableColoursByLdrawCode();
        $this->ldconfig->entries()
            ->where('meta', 'colour')
            ->each(function (array $color) use ($rbColors) {
                $c = LdrawColour::where('name', $color['name'])->where('code', '!=', $color['code'])->first();
                if (!is_null($c)) {
                    $c->delete();
                }
                $params = Arr::get($color, 'material_params', []);
                $rbColor = $rbColors?->get((string) $color['code']);
                $rbValues = is_null($rbColors) ? [] : [
                    'rebrickable_id' => $rbColor['id'] ?? null,
                    'rebrickable_name' => $rbColor['name'] ?? null,
                ];
                $color = [
                    'name' => $color['name'],
                    'code' => $color['code'],
                    'value' => $color['value'],
                    'edge' => $color['edge'],
                    'alpha' => $color['alpha'],
                    'luminance' => $color['luminance'],
                    'chrome' => $color['material'] == 'CHROME',
                    'pearlescent' => $color['material'] == 'PEARLESCENT',
                    'rubber' => $color['material'] == 'RUBBER',
                    'matte_metallic' => $color['material'] == 'MATTE_METALLIC',
                    'metal' => $color['material'] == 'METAL',
                    'glitter' => Arr::get($params, 'material_type') == 'GLITTER',
                    'speckle' => Arr::get($params, 'material_type') == 'SPECKLE',
                    'fabric' => Arr::get($params, 'material_type') == 'FABRIC',
                    'material_fabric_type' => Arr::get($params, 'fabric_type'),
                    'material_value' => Arr::get($params, 'value'),
                    'material_alpha' => Arr::get($params, 'alpha'),
                    'material_luminance' => Arr::get($params, 'luminance'),
                    'material_fraction' => Arr::get($params, 'fraction'),
                    'material_vfraction' => Arr::get($params, 'vfraction'),
                    'material_size' => Arr::get($params, 'size'),
                    'material_minsize' => Arr::get($params, 'minsize'),
                    'material_maxsize' => Arr::get($params, 'maxsize'),
                    ...$rbValues,
                ];
                LdrawColour::updateOrCreate(['code' => $color['code']], $color);
            });
        foreach ([CacheKey::LdrawColourCodes, CacheKey::LdrawColourOptions, CacheKey::LdrawColourCodesToRebrickable] as $key) {
            $this->cache->reset($key);
            $this->cache->warm($key);
        }
    }

    /**
     * Rebrickable colour id and name keyed by LDraw colour code. When several Rebrickable colours map to
     * the same code the lowest id wins, and the [Unknown] colour (-1) is never used.
     *
     * @return ?Collection<string, array{id: int, name: string}> null when the CSV downloads are missing
     */
    protected function rebrickableColoursByLdrawCode(): ?Collection
    {
        try {
            $names = $this->rebrickableCsv->rows(RebrickableCsvFile::Colors)->pluck('name', 'id')->all();
            $mappings = $this->rebrickableCsv->rows(RebrickableCsvFile::ColorMappings);
        } catch (FileNotFoundException) {
            return null;
        }

        return $mappings
            ->filter(fn (array $row) => $row['ext_system'] === 'LD' && $row['id'] !== '-1' && isset($names[$row['id']]))
            ->sortBy(fn (array $row) => (int) $row['id'])
            ->unique('ext_id')
            ->mapWithKeys(fn (array $row) => [$row['ext_id'] => ['id' => (int) $row['id'], 'name' => $names[$row['id']]]])
            ->collect();
    }
}
