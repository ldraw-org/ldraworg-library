<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection;

use App\Models\Traits\HasPartRelease;
use App\Models\Traits\HasLicense;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;

class Part extends Model
{
    use 
        HasLicense, 
        HasPartRelease, 
        HasUser, 
        HasGraphRelationships;

    protected $fillable = [
        'user_id',
        'part_category_id',
        'part_license_id',
        'part_type_id',
        'part_release_id',
        'part_type_qualifier_id',
        'description',
        'filename',
        'header',
        'cmdline',
        'bfc',
        'official_part_id',
        'unofficial_part_id',
    ];

    protected $with = ['release', 'type'];

    protected $casts = [
        'delete_flag' => 'boolean',
        'manual_hold_flag' => 'boolean',
        'minor_edit_data' => AsArrayObject::class,
        'missing_parts' => 'array',
    ];

    public function getPivotTableName(): string
    {
        return 'related_parts';
    }

    public function getParentKeyName(): string
    {
        return 'parent_id';
    }
  
    public function getChildKeyName(): string
    {
        return 'subpart_id';
    }

    public function category(): BelongsTo 
    {
        return $this->belongsTo(PartCategory::class, 'part_category_id', 'id');
    }

    public function type(): BelongsTo 
    {
        return $this->belongsTo(PartType::class, 'part_type_id', 'id');
    }

    public function type_qualifier(): BelongsTo 
    {
        return $this->belongsTo(PartTypeQualifier::class, 'part_type_qualifier_id', 'id');
    }

    public function subparts(): BelongsToMany 
    {
        return $this->children();
    }

    public function keywords(): BelongsToMany 
    {
        return $this->belongsToMany(PartKeyword::class, 'parts_part_keywords', 'part_id', 'part_keyword_id')->orderBy('keyword');
    }

    public function notification_users(): BelongsToMany 
    {
        return $this->belongsToMany(User::class, 'user_part_notifications', 'part_id', 'user_id');
    }

    public function votes(): HasMany 
    {
        return $this->hasMany(Vote::class, 'part_id', 'id');
    }

    public function events(): HasMany 
    {
        return $this->hasMany(PartEvent::class, 'part_id', 'id');
    }

    public function history(): HasMany 
    {
        return $this->hasMany(PartHistory::class, 'part_id', 'id')->oldest();
    }

    public function help(): HasMany 
    {
        return $this->hasMany(PartHelp::class, 'part_id', 'id')->orderBy('order');
    }

    public function body(): HasOne 
    {
        return $this->hasOne(PartBody::class, 'part_id', 'id');
    }
    
    public function official_part(): HasOne
    {
        return $this->HasOne(Part::class, 'official_part_id', 'id');
    }

    public function unofficial_part(): HasOne
    {
        return $this->HasOne(Part::class, 'unofficial_part_id', 'id');
    }

    public function scopeName(Builder $query, string $name): void
    {
        $name = str_replace('\\', '/', $name);
        if (pathinfo($name, PATHINFO_EXTENSION) == "png") {
            $name = "textures/{$name}";
        }

        $query->where(function ($q) use ($name) {
            $q->orWhere('filename', "p/{$name}")->orWhere('filename', "parts/{$name}");
        });
    }

    public function scopePartStatus(Builder $query, string $status): void
    {
        switch ($status) {
            case 'certified':
                $query->where('vote_sort', 1);
                break; 
            case 'adminreview':
                $query->where('vote_sort', 2);
                break; 
            case 'memberreview':
                $query->where('vote_sort', 3);
                break; 
            case 'held':
                $query->where('vote_sort', 5);
                break; 
        } 
    }
    
    public function scopeSearchPart (Builder $query, string $search, string $scope): void 
    {
        if ($search !== '') {
            //Pull the terms out of the search string
            $pattern = '#([^\s"]+)|"([^"]*)"#u';
            preg_match_all($pattern, $search, $matches, PREG_SET_ORDER);

            foreach($matches as $m) {
                $char = '\\';
                $term = str_replace(
                    [$char, '%', '_'],
                    [$char.$char, $char.'%', $char.'_'],
                    $m[count($m)-1]
                );
                switch ($scope) {
                case 'description':
                    $query->where(function(Builder $q) use ($term) {
                        $q->orWhere('filename', 'LIKE', "%{$term}%")->orWhere('description', 'LIKE', "%{$term}%");
                    });
                    break;
                case 'filename':
                case 'header':
                    $query->where($scope, 'LIKE', "%{$term}%");
                    break;
                case 'file':
                    $query->where(function(Builder $q) use ($term) {
                        $q->orWhere('header', 'LIKE', "%{$term}%")->orWhereRelation('body', 'body', 'LIKE', "%{$term}%");
                    });
                    break;
                default:  
                    $query->where('header', 'LIKE', "%{$term}%");
                    break;
                }
            }
        }
        else {
            $query->where('filename', '');
        }
    }

    public function scopePatterns(Builder $query, string $basepart): void 
    {
        $query->where(function(Builder $q) use ($basepart) {
            $q->where('filename', 'like', "parts/{$basepart}p__.dat")->orWhere('filename', 'like', "parts/{$basepart}p___.dat");
        });
    }

    public function scopeComposites(Builder $query, string $basepart): void 
    {
        $query->where(function(Builder $q) use ($basepart) {
            $q->where('filename', 'like', "parts/{$basepart}c__.dat")->orWhere('filename', 'like', "parts/{$basepart}c___.dat");
        });
    }

    public function scopeStickerShortcuts(Builder $query, string $basepart): void 
    {
        $query->where(function(Builder $q) use ($basepart) {
            $q->where('filename', 'like', "parts/{$basepart}d__.dat")->orWhere('filename', 'like', "parts/{$basepart}d___.dat");
        });
    }

    public function scopeAdminReady(Builder $query): void
    {
        $query->unofficial()
            ->whereIn('part_type_id', PartType::where('folder', 'parts/')->pluck('id')->all())
            ->whereHas('descendantsAndSelf', function ($q){
                $q->where('vote_sort', '=', 2);
            })
            ->whereDoesntHave('descendantsAndSelf', function ($q){
                $q->where('vote_sort', '>', 2);
            });
    }

    public function scopeUserReady(Builder $query): void
    {
        $query->unofficial()
            ->whereIn('part_type_id', PartType::where('folder', 'parts/')->pluck('id')->all())
            ->whereDoesntHave('descendantsAndSelf', function ($q) {
                $q->where('vote_sort', '5');
            })
            ->whereHas('descendantsAndSelf', function ($q) {
                $q->has('votes', '<=', 1)
                ->whereDoesntHave('votes', function($q){
                    $q->where('user_id', auth()->user()->id);
                })
                ->where('vote_sort', 3);
            });
    }

    public function isTexmap(): bool {
      return $this->type->format == 'png';
    }

    public function isUnofficial(): bool {
      return is_null($this->part_release_id);
    }

    public function hasPatterns(): bool {
      $basepart = $this->basePart();
      return $this->type->folder == 'parts/' && self::where(function($q) use ($basepart) {
        $q->where('filename', 'like', "parts/{$basepart}p__.dat")->orWhere('filename', 'like', "parts/{$basepart}p___.dat");
      })->count() > 0;
    }

    public function hasComposites(): bool {
      $basepart = $this->basePart();
      return $this->type->folder == 'parts/' && self::where(function($q) use ($basepart) {
        $q->where('filename', 'like', "parts/{$basepart}c__.dat")->orWhere('filename', 'like', "parts/{$basepart}c___.dat");
      })->count() > 0;
    }
    public function hasStickerShortcuts(): bool {
      $basepart = $this->basePart();
      return $this->type->folder == 'parts/' && self::where(function($q) use ($basepart) {
        $q->where('filename', 'like', "parts/{$basepart}d__.dat")->orWhere('filename', 'like', "parts/{$basepart}d___.dat");
      })->count() > 0;
    }

    public function basePart(): string {
      $number = basename($this->filename);
      preg_match(config('ldraw.patterns.basepart'), $number, $matches);
      return $matches[1] ?? '';
    }
    public function libFolder(): string {
      return $this->isUnofficial() ? 'unofficial' : 'official';
    }
        
    public function name(): string {
      return str_replace('/', '\\', str_replace(["parts/", "p/"], '', $this->filename));
    }

    public function get(bool $dos = true, bool $dataFile = false): string {
      if ($this->isTexmap()) {
        if ($dataFile === true) {
            $data = str_split($this->body->body, 80);
            $file = "0 !DATA " . str_replace(['parts/textures/', 'p/textures/'], '', $this->filename) . "\n";
            $file .= "0 !: " . implode("\n0 !: ", $data) . "\n";
            if ($dos === true) {
                $file = preg_replace('#\R#us', "\r\n", $file);
            }
        } else {
            $file = base64_decode($this->body->body);
        }
      }
      else {
        $file = rtrim($this->header) . "\n\n" . ($this->body->body ?? '');
        if ($dos === true) {
            $file = preg_replace('#\R#us', "\r\n", $file);
        }
      }
      return $file;
    }
    
    public function updateVoteData(): void 
    {
        if (!$this->isUnofficial()) {
            return;
        }
        $data = array_merge(['A' => 0, 'C' =>0, 'H' => 0, 'T' => 0], $this->votes->pluck('vote_type_code')->countBy()->all());
        if ($data['H'] != 0) {
            $this->vote_sort = 5;
        }
        // Needs votes      
        elseif (($data['C'] + $data['A'] < 2) && $data['T'] == 0) {
            $this->vote_sort = 3;
        }  
        // Awaiting Admin      
        elseif ($data['T'] == 0 && $data['A'] == 0 && $data['C'] >= 2) {
            $this->vote_sort = 2;
        }
        // Certified      
        elseif (($data['A'] > 0 && ($data['C'] + $data['A']) > 2) || $data['T'] > 0) {
            $this->vote_sort = 1;
        }
        $this->saveQuietly();
    }
  
    public function setKeywords(array|Collection $keywords): void
    {
        if ($keywords instanceof Collection) {
            $this->keywords()->sync($keywords->pluck('id')->all());
        } else {
            $kws = PartKeyword::whereIn('keyword', $keywords)->get();
            $ids = $kws->pluck('id')->all();
            $new_keywords = array_udiff($keywords, $kws->pluck('keyword')->all(), 'strcasecmp');
            foreach($new_keywords as $kw) {
                $ids[] = PartKeyword::create(['keyword' => $kw])->id;
            }
            $this->keywords()->sync($ids);    
        }
    }

    public function setHelp(array|Collection $help): void
    {
        $this->help()->delete();
        if ($help instanceof Collection) {
            foreach($help as $h) {
                PartHelp::create(['part_id' => $this->id, 'order' => $h->order, 'text' => $h->text]);
            }    
        } else {
            foreach($help as $index => $h) {
                PartHelp::create(['part_id' => $this->id, 'order' => $index, 'text' => $h]);
            }    
        }
    }

    public function setHistory(array|Collection $history): void 
    {
        $this->history()->delete();
        if ($history instanceof Collection) {
            foreach ($history as $hist) {
                PartHistory::create([
                    'user_id' => $hist->user->id, 
                    'part_id' => $this->id, 
                    'created_at' => $hist->created_at, 
                    'comment' => $hist->comment
                ]);
            }
        } else {
            foreach ($history as $hist) {
                $u = User::fromAuthor($hist['user'])->first();
                PartHistory::create([
                    'user_id' => $u->id, 
                    'part_id' => $this->id, 
                    'created_at' => $hist['date'], 
                    'comment' => $hist['comment']
                ]);
            }                
        }
    }

    public function setSubparts(array|Collection $subparts): void 
    {
        if ($subparts instanceof Collection) {
            $this->subparts()->sync($subparts->pluck('id')->all());
            $this->missing_parts = [];
            $this->save();
        } else {
            $subs = [];
            foreach ($subparts['subparts'] ?? [] as $s) {
                $s = str_replace('\\', '/', $s);
                $subs[] = "parts/{$s}";
                $subs[] = "p/{$s}";
            }
            foreach ($subparts['textures'] ?? [] as $s) {
                $s = str_replace('\\', '/', $s);
                $subs[] = "parts/textures/{$s}";
                $subs[] = "p/textures/{$s}";
            }
            $subps = Part::whereIn('filename', $subs)->get();
            $this->subparts()->sync($subps->pluck('id')->all());

            $existing_subs = $subps->pluck('filename')->all();
            $esubs = [];
            foreach ($existing_subs ?? [] as $s) {
                $s = str_replace('textures/', '', $s);
                $s = str_replace(['parts/', 'p/'], '', $s);
                $esubs[] = str_replace('/', '\\', $s);
            }
            $missing = collect(array_merge($subparts['subparts'] ?? [], $subparts['textures'] ?? []))->diff(collect($esubs));
            $this->missing_parts = $missing;
            $this->save();
        }
    }

    public function setBody(string|PartBody $body): void
    {
        if ($body instanceof PartBody) {
            $body = $body->body;
        }
        if (is_null($this->body)) {
            PartBody::create([
                'part_id' => $this->id,
                'body' => $body,
            ]);
        } else {
            $this->body->body = $body;
            $this->body->save();
        }
    }

    public function generateHeader(): void
    {
        $header = [];
        $header[] = "0 {$this->description}" ?? '' ;
        $header[] = "0 Name: {$this->name()}" ?? '';
        $header[] = $this->user->toString();

        $typestr = $this->type->toString(is_null($this->release));
        if (!is_null($this->type_qualifier)) {
            $typestr .= " {$this->type_qualifier->type}";
        }
        if (!is_null($this->release)) {
            $typestr .= $this->release->toString();
        }
        $header[] = $typestr;
        $header[] = $this->license->toString();
        $header[] = '';

        if ($this->help->count() > 0) {
            foreach($this->help as $h) {
                $header[] = "0 !HELP {$h->text}";
            }
            $header[] = '';
        }

        if (!is_null($this->bfc)) {
            $header[] = "0 BFC CERTIFY {$this->bfc}";
            $header[] = '';
        } elseif (!$this->isTexmap()) {
            $header[] = "0 BFC NOCERTIFY";
            $header[] = '';
        }

        $addBlank = false;
        if (!is_null($this->category) && ($this->type->type === 'Part' || $this->type->type === 'Shortcut')) {
            $d = trim($this->description);
            if ($d !== '' && in_array($d[0], ['~', '|', '=', '_']))
            {
                $d = trim(substr($d, 1));
            }    
            $cat = mb_strstr($d, " ", true);
            if ($cat != $this->category->category) {
                $header[] = $this->category->toString();
                $addBlank = true;
            }
        }
        if ($this->keywords->count() > 0) {
            $kws = $this->keywords->pluck('keyword')->all();
            foreach ($kws as $index => $kw) {
                if (array_key_first($kws) == $index) {
                    $kwline = "0 !KEYWORDS ";
                }
                if ($kwline !== "0 !KEYWORDS " && mb_strlen("{$kwline}, {$kw}") > 80) {
                    $header[] = $kwline;
                    $kwline = "0 !KEYWORDS ";
                }
                if ($kwline !== "0 !KEYWORDS ") {
                    $kwline .= ", ";
                }
                $kwline .= $kw;
                if (array_key_last($kws) == $index) {
                    $header[] = $kwline;
                    $addBlank = true;
                }
            }  
        }
        if ($addBlank === true) {
            $header[] = '';
        }

        if (!is_null($this->cmdline)) {
            $header[] = "0 !CMDLINE {$this->cmdline}";
            $header[] = '';
        }

        if ($this->history->count() > 0) {
            foreach($this->history as $h) {
                $header[] = $h->toString();
            }
        }

        $this->header = implode("\n", $header);
        $this->save();
    }
/*  
    public function allSubparts(): Collection
    {
        $parts = new Collection();
        if ($this->subparts->count() == 0) {
            return $parts;
        }
        $parts = $parts->concat($this->subparts);
        foreach ($this->subparts as $s) {
            $parts = $parts->concat($s->allSubparts());
        }
        return $parts->unique();
    }

    public function allParents(): Collection
    {
        $parts = new Collection();
        if ($this->parents->count() == 0) {
            return $parts;
        }
        $parts = $parts->concat($this->parents);
        foreach ($this->parents as $s) {
            $parts = $parts->concat($s->allParents());
        }
        return $parts->unique();
    }
*/

    public function deleteRelationships(): void 
    {
        $this->history()->delete();
        $this->votes()->delete();
        $this->events()->delete();
        $this->help()->delete();
        $this->body->delete();
        $this->keywords()->sync([]);
        $this->subparts()->sync([]);
        $this->notification_users()->sync([]);
        if (!is_null($this->unofficial_part_id)) {
            $p = self::find($this->unofficial_part_id);
            $p->official_part_id = null;
            $p->save();
        }
        if (!is_null($this->official_part_id)) {
            $p = self::find($this->official_part_id);
            $p->unofficial_part_id = null;
            $p->save();
        }
    }

    public function putDeletedBackup(): void 
    {
        $t = time();
        Storage::disk('local')->put("deleted/library/{$this->filename}.{$t}", $this->get());
        Storage::disk('local')->put('deleted/library/' . str_replace(['.png', '.dat'], '.evt', $this->filename). ".{$t}", $this->events->toJson());
    }

    public function statusText(): string {
        if ($this->isUnofficial()) {
            $codes = array_merge(['A' => 0, 'C' => 0, 'H' => 0, 'T' => 0], $this->votes->pluck('vote_type_code')->countBy()->all());
            return match ($this->vote_sort) {
                1 => 'Certified!',
                2 => 'Needs Admin Review',
                3 => $codes['A'] + $codes['C'] == 1 ? 'Needs 1 More Vote' : 'Needs 2 More Votes',
                5 => 'Errors Found'
            };
        } else {
            return "Update {$this->release->name}";
        }
    }

    public function statusCode(): string {
        if ($this->isUnofficial()) {
            $code = '(';
            $codes = array_merge(['A' => 0, 'C' => 0, 'H' => 0, 'T' => 0], $this->votes->pluck('vote_type_code')->countBy()->all());
            foreach(['T', 'A', 'C', 'H'] as $letter) {
                $code .= str_repeat($letter, $codes[$letter]);
            } 
            return $code .= is_null($this->official_part_id) ? 'N)' : 'F)';
        } else {
            return $this->partStatusText($this);
        }  
    }

}
