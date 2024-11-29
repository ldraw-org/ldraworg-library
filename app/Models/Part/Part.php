<?php

namespace App\Models\Part;

use App\Enums\EventType;
use App\Enums\VoteType;
use App\Models\StickerSheet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Traits\HasPartRelease;
use App\Models\Traits\HasLicense;
use App\Models\Traits\HasUser;
use App\Models\User;
use App\Models\Vote;
use App\Observers\PartObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;

#[ObservedBy([PartObserver::class])]
class Part extends Model
{
    use HasGraphRelationships;
    use HasLicense;
    use HasPartRelease;
    use HasUser;

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
        'unofficial_part_id',
        'can_release',
        'part_check_messages',
        'ready_for_admin',
        'base_part_id',
    ];

    protected $with = ['release', 'type'];

    /**
    * @return array{
    *     delete_flag: 'boolean',
    *     manual_hold_flag: 'boolean',
    *     has_minor_edit: 'boolean',
    *     missing_parts: 'array',
    *     can_release: 'boolean',
    *     marked_for_release: 'boolean',
    *     part_check_messages: 'Illuminate\Database\Eloquent\Casts\AsArrayObject',
    *     ready_for_admin: 'boolean'
    * }
    */
    protected function casts(): array
    {
        return [
            'delete_flag' => 'boolean',
            'manual_hold_flag' => 'boolean',
            'has_minor_edit' => 'boolean',
            'missing_parts' => 'array',
            'can_release' => 'boolean',
            'marked_for_release' => 'boolean',
            'part_check_messages' => AsArrayObject::class,
            'ready_for_admin' => 'boolean',
        ];
    }

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
        return $this->belongsToMany(User::class, 'user_part_notifications');
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
        return $this->hasMany(PartHelp::class, 'part_id', 'id')->ordered();
    }

    public function body(): HasOne
    {
        return $this->hasOne(PartBody::class, 'part_id', 'id');
    }

    public function unofficial_part(): BelongsTo
    {
        return $this->BelongsTo(Part::class, 'unofficial_part_id', 'id');
    }

    public function official_part(): HasOne
    {
        return $this->HasOne(Part::class, 'unofficial_part_id', 'id');
    }

    public function base_part(): BelongsTo
    {
        return $this->BelongsTo(Part::class, 'base_part_id', 'id');
    }

    public function suffix_parts(): HasMany
    {
        return $this->HasMany(Part::class, 'base_part_id', 'id');
    }

    public function patterns(): HasMany
    {
        return $this->HasMany(Part::class, 'base_part_id', 'id')->where('is_pattern', true);
    }

    public function composites(): HasMany
    {
        return $this->HasMany(Part::class, 'base_part_id', 'id')->where('is_composite', true);
    }

    public function shortcuts(): HasMany
    {
        return $this->HasMany(Part::class, 'base_part_id', 'id')->whereRelation('category', 'category', 'Sticker Shortcut');
    }

    public function sticker_sheet(): BelongsTo
    {
        return $this->BelongsTo(StickerSheet::class, 'sticker_sheet_id', 'id');

    }

    public function unknown_part_number(): BelongsTo
    {
        return $this->BelongsTo(UnknownPartNumber::class, 'unknown_part_number_id', 'id');

    }

    protected function errors(): Attribute
    {
        return Attribute::make(
            get: fn (string $value, array $attributes) => $attributes['part_check_messages']['errors'] ?? [],
            set: fn (array $value, array $attributes) => $attributes['part_check_messages']['errors'] = $value,
        );
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

    public function scopeSearchPart(Builder $query, string $search, string $scope): void
    {
        if ($search !== '') {
            //Pull the terms out of the search string
            $pattern = '#([^\s"]+)|"([^"]*)"#u';
            preg_match_all($pattern, $search, $matches, PREG_SET_ORDER);

            foreach ($matches as $m) {
                $char = '\\';
                $term = str_replace(
                    [$char, '%', '_'],
                    [$char.$char, $char.'%', $char.'_'],
                    $m[count($m) - 1]
                );
                switch ($scope) {
                    case 'description':
                        $query->where(function (Builder $q) use ($term) {
                            $q->orWhere('filename', 'LIKE', "%{$term}%")->orWhere('description', 'LIKE', "%{$term}%");
                        });
                        break;
                    case 'filename':
                    case 'header':
                        $query->where($scope, 'LIKE', "%{$term}%");
                        break;
                    case 'file':
                        $query->where(function (Builder $q) use ($term) {
                            $q->orWhere('header', 'LIKE', "%{$term}%")->orWhereRelation('body', 'body', 'LIKE', "%{$term}%");
                        });
                        break;
                    default:
                        $query->where('header', 'LIKE', "%{$term}%");
                        break;
                }
            }
        } else {
            $query->where('filename', '');
        }
    }

    public function scopeAdminReady(Builder $query): void
    {
        $query->whereNull('part_release_id')
            ->whereRelation('type', 'folder', 'parts/')
            ->where('ready_for_admin', true)
            ->whereHas('descendantsAndSelf', function ($q) {
                $q->where('vote_sort', '=', 2);
            });
    }

    public function isTexmap(): bool
    {
        return $this->type->format == 'png';
    }

    public function isUnofficial(): bool
    {
        return is_null($this->part_release_id);
    }

    public function lastChange(): Carbon
    {
        $recent_change = $this->events()
            ->whereIn(
                'event_type',
                [EventType::Submit, EventType::Rename, EventType::HeaderEdit, EventType::Release]
            )
            ->latest()
            ->first();
        if (is_null($recent_change)) {
            return $this->isUnofficial() ? $this->created_at : $this->release->created_at;
        }
        // Zip files only save in 2 sec, even increments
        // This ensures consistancy in time reporting
        if ($recent_change->created_at->format('U') % 2 == 1) {
            return $recent_change->created_at->subSecond();
        }
        return $recent_change->created_at;
    }

    public function libFolder(): string
    {
        return $this->isUnofficial() ? 'unofficial' : 'official';
    }

    public function name(): string
    {
        return str_replace('/', '\\', str_replace(["parts/", "p/"], '', $this->filename));
    }

    public function get(bool $dos = true, bool $dataFile = false): string
    {
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
        } else {
            $file = rtrim($this->header) . "\n\n" . ($this->body->body ?? '');
            if ($dos === true) {
                $file = preg_replace('#\R#us', "\r\n", $file);
            }
        }
        return $file;
    }

    protected function voteTypeCount(): array
    {
        return array_merge(
            [VoteType::AdminCertify->value => 0, VoteType::Certify->value => 0, VoteType::Hold->value => 0, VoteType::AdminFastTrack->value => 0],
            $this->votes->pluck('vote_type')->countBy(fn(VoteType $vt) => $vt->value)->all()
        );
    }

    public function updateVoteSort(): void
    {
        if (!$this->isUnofficial()) {
            return;
        }
        $old_sort = $this->vote_sort;
        $data = $this->voteTypeCount();

        if ($data[VoteType::Hold->value] != 0) {
            $this->vote_sort = 5;
        }
        // Needs votes
        elseif (($data[VoteType::Certify->value] + $data[VoteType::AdminCertify->value] < 2) && $data[VoteType::AdminFastTrack->value] == 0) {
            $this->vote_sort = 3;
        }
        // Awaiting Admin
        elseif ($data[VoteType::AdminFastTrack->value] == 0 && $data[VoteType::AdminCertify->value] == 0 && $data[VoteType::Certify->value] >= 2) {
            $this->vote_sort = 2;
        }
        // Certified
        elseif (($data[VoteType::AdminCertify->value] > 0 && ($data[VoteType::Certify->value] + $data[VoteType::AdminCertify->value]) > 2) || $data[VoteType::AdminFastTrack->value] > 0) {
            $this->vote_sort = 1;
        }
        $this->saveQuietly();
        if (
            ($old_sort <= 2 && $this->vote_sort > 2) ||
            ($old_sort > 2 && $this->vote_sort <= 2)
        ) {
            $this->updateReadyForAdmin();
        }
    }

    public function updateReadyForAdmin(): void
    {
        $old = $this->ready_for_admin;
        $this->ready_for_admin = $this->vote_sort <= 2 && $this->descendants->where('vote_sort', '>', 2)->count() == 0;
        if ($old != $this->ready_for_admin) {
            $this->saveQuietly();
            foreach ($this->ancestors as $p) {
                $p->ready_for_admin = $p->vote_sort <= 2 && $p->descendants->where('vote_sort', '>', 2)->count() == 0;
                $p->saveQuietly();
            }
        }
    }

    public function setKeywords(array|Collection $keywords): void
    {
        if ($keywords instanceof Collection) {
            $this->keywords()->sync($keywords->pluck('id')->all());
        } else {
            $keywords = array_filter($keywords, fn(string $value) => strlen(trim($value)) > 0);
            $kws = PartKeyword::whereIn('keyword', $keywords)->get();
            $ids = $kws->pluck('id')->all();
            $new_keywords = array_udiff($keywords, $kws->pluck('keyword')->all(), 'strcasecmp');
            foreach ($new_keywords as $kw) {
                $ids[] = PartKeyword::create(['keyword' => $kw])->id;
            }
            $this->keywords()->sync($ids);
        }
    }

    public function setHelp(array|Collection $help): void
    {
        $this->help()->delete();
        if ($help instanceof Collection) {
            foreach ($help as $h) {
                /** @var PartHelp $h */
                PartHelp::create(['part_id' => $this->id, 'order' => $h->order, 'text' => $h->text]);
            }
        } else {
            foreach ($help as $index => $h) {
                /** @var PartHelp $h */
                PartHelp::create(['part_id' => $this->id, 'order' => $index, 'text' => $h]);
            }
        }
    }

    public function setHistory(array|Collection $history): void
    {
        $this->history()->delete();
        if ($history instanceof Collection) {
            foreach ($history as $hist) {
                /** @var PartHistory $hist */
                PartHistory::create([
                    'user_id' => $hist->user->id,
                    'part_id' => $this->id,
                    'created_at' => $hist->created_at,
                    'comment' => $hist->comment
                ]);
            }
        } else {
            foreach ($history as $hist) {
                /** @var PartHistory $hist */
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
            $subps = Part::whereIn('filename', $subs)->where('filename', '<>', $this->filename)->get();
            $this->subparts()->sync($subps->pluck('id')->all());

            $existing_subs = $subps->pluck('filename')->all();
            $esubs = [];
            foreach ($existing_subs ?? [] as $s) {
                $s = str_replace('textures/', '', $s);
                $s = str_replace(['parts/', 'p/'], '', $s);
                $esubs[] = str_replace('/', '\\', $s);
            }
            $missing = collect(array_merge($subparts['subparts'] ?? [], $subparts['textures'] ?? []))->diff(collect($esubs))->values()->all();
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
            foreach ($this->help as $h) {
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
            if ($d !== '' && in_array($d[0], ['~', '|', '=', '_'])) {
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
            $kwline = '';
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
            foreach ($this->history as $h) {
                $header[] = $h->toString();
            }
        }

        $this->header = implode("\n", $header);
        $this->saveQuietly();
    }

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
        if (!is_null($this->official_part)) {
            $p = $this->official_part;
            $this->official_part->unofficial_part()->dissociate();
            $p->save();
        }
    }

    public function putDeletedBackup(): void
    {
        $t = time();
        Storage::disk('local')->put("deleted/library/{$this->filename}.{$t}", $this->get());
        Storage::disk('local')->put('deleted/library/' . str_replace(['.png', '.dat'], '.evt', $this->filename). ".{$t}", $this->events->toJson());
    }

    public function statusText(): string
    {
        if ($this->isUnofficial()) {
            $codes = $this->voteTypeCount();
            return match ($this->vote_sort) {
                1 => 'Certified!',
                2 => 'Needs Admin Review',
                3 => $codes[VoteType::AdminCertify->value] + $codes[VoteType::Certify->value] == 1 ? 'Needs 1 More Vote' : 'Needs 2 More Votes',
                5 => 'Errors Found',
                default => 'Needs More Votes'
            };
        } else {
            return "Update {$this->release->name}";
        }
    }

    public function statusCode(): string
    {
        if ($this->isUnofficial()) {
            $code = '(';
            $codes = $this->voteTypeCount();
            foreach ([VoteType::AdminFastTrack->value, VoteType::AdminCertify->value, VoteType::Certify->value, VoteType::Hold->value] as $letter) {
                $code .= str_repeat($letter, $codes[$letter]);
            }
            return $code .= is_null($this->official_part) ? 'N)' : 'F)';
        } else {
            return $this->statusText();
        }
    }

}
