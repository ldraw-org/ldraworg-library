<?php

namespace App\Services\LDraw\Managers;

use App\Models\Avatar;
use App\Models\LdrawColour;
use App\Services\Cache\CacheKey;
use App\Services\Cache\CacheService;
use App\Services\LDraw\Rebrickable;
use App\Services\Parser\ParsedPartCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LDConfigManager
{
    public function __construct(
        protected Rebrickable $rb,
        protected CacheService $cache
    ) {

    }

    public function getLDConfig(): ParsedPartCollection
    {
        $ldconfig = Storage::get('library/official/LDConfig.ldr');
        return new ParsedPartCollection($ldconfig)->where('invalid', false);
    }

    public function importColours(): void
    {
        $rbColors = $this->rb->getColors();
        $this->getLDConfig()
            ->where('meta', 'colour')
            ->each(function (array $color) use ($rbColors) {
                $c = LdrawColour::where('name', $color['name'])->where('code', '!=', $color['code'])->first();
                if (!is_null($c)) {
                    $c->delete();
                }
                $params = Arr::get($color, 'material_params', []);
                $rbId = $rbColors->where('id', '!=', '-1')->first(fn (array $rbValues) => in_array($color['code'], Arr::get($rbValues, 'external_ids.LDraw.ext_ids', [])))['id'] ?? null;
                $rbName = $rbColors->where('id', '!=', '-1')->first(fn (array $rbValues) => in_array($color['code'], Arr::get($rbValues, 'external_ids.LDraw.ext_ids', [])))['name'] ?? null;
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
                    'rebrickable_id' => $rbId,
                    'rebrickable_name' => $rbName,
                ];
                LdrawColour::updateOrCreate(['code' => $color['code']], $color);
            });
        $this->cache->reset(CacheKey::LdrawColourCodes);
        $this->cache->warm(CacheKey::LdrawColourCodes);
        $this->cache->reset(CacheKey::LdrawColourCodesToRebrickable);
        $this->cache->warm(CacheKey::LdrawColourCodesToRebrickable);
    }

    public function ldrawColourCodes(): array
    {
        return $this->cache->remember(
            CacheKey::LdrawColourCodes,
            fn() => LdrawColour::pluck('code')->all()
        );
    }

    public function ldrawColourOptions(): array
    {
        return $this->cache->remember(
            CacheKey::LdrawColourOptions,
            fn() => LdrawColour::select('id','code','name', 'value')
                ->orderBy('code')
                ->get()
                ->mapWithKeys(
                    fn (LdrawColour $color) =>
                    [$color->code => "<span class=\"{$color->labelTextColor()} rounded px-2 py-1\" style=\"background-color: {$color->value}\">{$color->code} - {$color->name}</span>"]
                )
                ->all()
        );
    }

    public function ldrawColourCodesToRebrickable(): array
    {
        return $this->cache->remember(
            CacheKey::LdrawColourCodesToRebrickable,
            fn () => LdrawColour::pluck('rebrickable_id', 'code')->all()
        );
    }

    public function importAvatars(): void
    {
        $avatars = $this->getLDConfig()
            ->where('meta', 'avatar')
            ->map(function (array $avatar) {
                return [
                    'category' => $avatar['category'],
                    'description' => $avatar['description'],
                    'part' => $avatar['file'],
                    'matrix' => "{$avatar['a']} {$avatar['b']} {$avatar['c']} " .
                                "{$avatar['d']} {$avatar['e']} {$avatar['f']} " .
                                "{$avatar['g']} {$avatar['h']} {$avatar['i']}"
                ];
            })
            ->all();
        Avatar::upsert($avatars, uniqueBy: 'category', update: ['part', 'matrix', 'description']);
        CacheKey::AvatarParts->reset();
        CacheKey::AvatarParts->warm();
    }

    public function avatarParts(): array
    {
        return $this->cache->remember(
            CacheKey::AvatarParts,
            fn() => Avatar::pluck('part', 'category')->all()
        );
    }
}
