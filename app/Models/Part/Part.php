<?php

namespace App\Models\Part;

use App\Enums\EventType;
use App\Enums\ExternalSite;
use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartError;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Enums\VoteType;
use App\Models\RebrickablePart;
use App\Models\ReviewSummary\ReviewSummaryItem;
use App\Models\StickerSheet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Traits\HasPartRelease;
use App\Models\Traits\HasUser;
use App\Models\User;
use App\Models\Vote;
use App\Observers\PartObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;

/**
 * @mixin IdeHelperPart
 */
#[ObservedBy([PartObserver::class])]
class Part extends Model
{
    use HasGraphRelationships;
    use HasPartRelease;
    use HasUser;
    use HasFactory;

    protected $guarded = [];

    /**
    * @return array{
    *     type: 'App\Enums\PartType',
    *     type_qualifier: 'App\Enums\PartTypeQualifier',
    *     license: 'App\Enums\License',
    *     part_status: 'App\Enums\PartStatus',
    *     category => 'App\Enums\PartCategory',
    *     delete_flag: 'boolean',
    *     manual_hold_flag: 'boolean',
    *     has_minor_edit: 'boolean',
    *     missing_parts: 'array',
    *     can_release: 'boolean',
    *     marked_for_release: 'boolean',
    *     is_pattern: 'boolean',
    *     is_composite: 'boolean',
    *     is_dual_mould: 'boolean',
    *     part_check_messages: 'Illuminate\Database\Eloquent\Casts\AsArrayObject',
    *     ready_for_admin: 'boolean'
    *     rebrickable: 'Illuminate\Database\Eloquent\Casts\AsArrayObject',
    * }
    */
    protected function casts(): array
    {
        return [
            'type' => PartType::class,
            'type_qualifier' => PartTypeQualifier::class,
            'license' => License::class,
            'part_status' => PartStatus::class,
            'category' => PartCategory:: class,
            'delete_flag' => 'boolean',
            'manual_hold_flag' => 'boolean',
            'has_minor_edit' => 'boolean',
            'missing_parts' => 'array',
            'can_release' => 'boolean',
            'marked_for_release' => 'boolean',
            'is_pattern' => 'boolean',
            'is_composite' => 'boolean',
            'is_dual_mould' => 'boolean',
            'part_check_messages' => AsArrayObject::class,
            'ready_for_admin' => 'boolean',
            'rebrickable' => AsArrayObject::class,
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
        return $this->HasMany(Part::class, 'base_part_id', 'id')->where('category', PartCategory::StickerShortcut);
    }

    public function sticker_sheet(): BelongsTo
    {
        return $this->BelongsTo(StickerSheet::class, 'sticker_sheet_id', 'id');

    }

    public function unknown_part_number(): BelongsTo
    {
        return $this->BelongsTo(UnknownPartNumber::class, 'unknown_part_number_id', 'id');

    }

    public function review_summary_items(): HasMany
    {
        return $this->HasMany(ReviewSummaryItem::class, 'part_id', 'id');

    }

    public function rebrickable_part(): BelongsTo
    {
        if (!is_null($this->sticker_sheet_id) && $this->category != PartCategory::StickerShortcut) {
            return $this->sticker_sheet->rebrickable_part();
        } elseif ($this->type_qualifier == PartTypeQualifier::Alias) {
            return $this->subparts->first()->rebrickable_part();
        }
        return $this->belongsTo(RebrickablePart::class, 'rebrickable_part_id', 'id');
    }

    protected function errors(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => Arr::get(json_decode($attributes['part_check_messages'], true), 'errors', []),
        );
    }

    public function uncertified_subparts(): Collection
    {
        return $this
            ->descendants()
            ->whereIn('part_status', [\App\Enums\PartStatus::AwaitingAdminReview, \App\Enums\PartStatus::NeedsMoreVotes, \App\Enums\PartStatus::ErrorsFound])
            ->get();
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

    public function scopeSearchHeader(Builder $query, string $search): void
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
                $query->where('header', 'LIKE', "%{$term}%");
            }
        }
    }

    public function scopePartsFolderOnly(Builder $query): void
    {
        $query->whereIn('type', PartType::partsFolderTypes());
    }

    public function scopeCanHaveRebrickablePart(Builder $query)
    {
        $query->partsFolderOnly()
            ->whereDoesntHave('sticker_sheet')
            ->whereNull('type_qualifier')
            ->where('description', 'NOT LIKE', '~%')
            ->where('description', 'NOT LIKE', '|%')
            ->where('description', 'NOT LIKE', '%(Obsolete)')
            ->whereNotIn('category', [PartCategory::Moved, PartCategory::Obsolete]);
    }

    public function scopeHasError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->whereJsonContainsKey("part_check_messages->errors->{$error}");
    }

    public function scopeOrHasError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->orWhereJsonContainsKey("part_check_messages->errors->{$error}");
    }

    public function scopeDoesntHaveError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->whereJsonDoesntContainKey("part_check_messages->errors->{$error}");
    }

    public function scopeOrDoesntHaveError(Builder $query, string|PartError $error): void
    {
        if ($error instanceof PartError) {
            $error = $error->value;
        }
        $query->orWhereJsonDoesntContainKey("part_check_messages->errors->{$error}");
    }

    public function isTexmap(): bool
    {
        return $this->type->isImageFormat();
    }

    public function isUnofficial(): bool
    {
        return is_null($this->part_release_id);
    }

    public function isObsolete(): bool
    {
        return $this->category == PartCategory::Obsolete ||
            Str::of($this->description)->contains('(Obsolete)') ||
            Str::of($this->description)->startsWith('~Obsolete');
    }

    public function canSetRebrickablePart(): bool
    {
        return $this->type->inPartsFolder() &&
            !$this->isObsolete() &&
            $this->category != PartCategory::Moved &&
            is_null($this->type_qualifier) &&
            is_null($this->sticker_sheet_id) &&
            !Str::of($this->description)->startsWith('~') &&
            !Str::of($this->description)->startsWith('|');
    }

    public function getExternalSiteNumber(ExternalSite $external): ?string
    {
        if (is_null($this->rebrickable_part)) {
            $kw = $this->keywords
            ->first(fn (PartKeyword $kw) => Str::of($kw->keyword)->lower()->startsWith($external->value))?->keyword ?? '';
            $number = Str::of($kw)->lower()->chopStart("{$external->value} ")->trim();
            return $number == '' ? null : $number;
        }
        if ($external == ExternalSite::Rebrickable) {
            return $this->rebrickable_part->number;
        }
        $name = $this?->sticker_sheet->number ?? basename($this->filename, '.dat');
        $site_data = ($this?->rebrickable_part->{$external->value}) ?? [];
        return Arr::first($site_data, fn(string $number, int $key) => $number == $name) ?? Arr::first($site_data);
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

    public function imagePath(): string
    {
        return "{$this->libFolder()}/{$this->type->folder()}/" . basename($this->filename, ".{$this->type->format()}") . '.png';
    }

    public function imageThumbPath(): string
    {
        return "{$this->libFolder()}/{$this->type->folder()}/" . basename($this->filename, ".{$this->type->format()}") . '_thumb.png';
    }

    public function name(): string
    {
        return str_replace('/', '\\', str_replace(["parts/", "p/"], '', $this->filename));
    }

    public function previewValues(): array
    {
        $preview = is_null($this->preview) ? '16 0 0 0 1 0 0 0 1 0 0 0 1' : $this->preview;
        preg_match('/([0-9.-]+) ([0-9.-]+) ([0-9.-]+) ([0-9.-]+) ((?:[0-9.-]+ ){8}[0-9.-]+)/u', $preview, $matrix);
        return [
            'color' => $matrix[1],
            'x' => $matrix[2],
            'y' => $matrix[3],
            'z' => $matrix[4],
            'rotation' => $matrix[5]
        ];
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
                /*
                Removing the header embed code for now until the reason why it causing LDview to error is found.

                $png = new \Imagick();
                $png->readImageBlob(base64_decode($this->body->body));
                $png->setImageProperty('LDrawHeader', $this->header);
                $png->setImageColorspace(\imagick::COLORSPACE_SRGB);
                $png->setImageFormat('png');
                $file = $png->getImageBlob();
                */
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
            $this->votes->pluck('vote_type')->countBy(fn (VoteType $vt) => $vt->value)->all()
        );
    }

    public function updatePartStatus(): void
    {
        if (!$this->isUnofficial()) {
            $this->part_status = PartStatus::Official;
            $this->saveQuietly();
        }
        $old_sort = $this->part_status;
        $data = $this->voteTypeCount();

        if ($data[VoteType::Hold->value] != 0) {
            $this->part_status = PartStatus::ErrorsFound;
        }
        elseif (($data[VoteType::Certify->value] + $data[VoteType::AdminCertify->value] < 2) && $data[VoteType::AdminFastTrack->value] == 0) {
            $this->part_status = PartStatus::NeedsMoreVotes;
        }
        elseif ($data[VoteType::AdminFastTrack->value] == 0 && $data[VoteType::AdminCertify->value] == 0 && $data[VoteType::Certify->value] >= 2) {
            $this->part_status = PartStatus::AwaitingAdminReview;
        }
        elseif (($data[VoteType::AdminCertify->value] > 0 && ($data[VoteType::Certify->value] + $data[VoteType::AdminCertify->value]) > 2) || $data[VoteType::AdminFastTrack->value] > 0) {
            $this->part_status = PartStatus::Certified;
        }
        if (
            (in_array($old_sort, [PartStatus::Certified, PartStatus::AwaitingAdminReview]) && !in_array($this->part_status, [PartStatus::Certified, PartStatus::AwaitingAdminReview])) ||
            (!in_array($old_sort, [PartStatus::Certified, PartStatus::AwaitingAdminReview]) && in_array($this->part_status, [PartStatus::Certified, PartStatus::AwaitingAdminReview]))
        ) {
            $this->updateReadyForAdmin();
        }
        $this->saveQuietly();
    }

    public function updateReadyForAdmin(): void
    {
        $old = $this->ready_for_admin;
        $this->ready_for_admin =
            in_array($this->part_status, [PartStatus::Certified, PartStatus::AwaitingAdminReview]) &&
            !$this->descendants()->whereIn('part_status', [PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound])->exists();
        if ($old != $this->ready_for_admin) {
            $this->saveQuietly();
            $this->ancestors->unique()->unofficial()->each(function (Part $p) {
                $p->ready_for_admin =
                    in_array($p->part_status, [PartStatus::Certified, PartStatus::AwaitingAdminReview]) &&
                    !$p->descendants()->whereIn('part_status', [PartStatus::NeedsMoreVotes, PartStatus::ErrorsFound])->exists();
                $p->saveQuietly();
            });
        }
    }

    public function setKeywords(array|Collection $keywords): void
    {
        if (!$keywords instanceof Collection) {
            if (is_array($keywords)) {
                $keywords = collect($keywords);
            }
            $keywords = $keywords
                ->filter()
                ->map(fn (string $kw, int $key): string => Str::of($kw)->trim()->squish()->toString())
                ->filter()
                ->map(fn (string $kw, int $key): array =>
                    ['id' => PartKeyword::firstOrCreate(['keyword' => $kw])->id]
                );
        }
        $keywords = $keywords->unique('id')->pluck('id')->filter()->all();
        $this->keywords()->sync($keywords);
    }

    public function setExternalSiteKeywords(bool $updateOfficial = false): void
    {
        $this->load('rebrickable_part');
        if (!is_null($this->rebrickable_part) && ($updateOfficial || $this->isUnofficial())) {
            $part_num = basename($this->filename, '.dat');
            $okws = $this->keywords
                ->filter(fn (PartKeyword $key) =>
                    Str::of($key->keyword)->lower()->startsWith('rebrickable') ||
                    Str::of($key->keyword)->lower()->startsWith('bricklink') ||
                    Str::of($key->keyword)->lower()->startsWith('brickset') ||
                    Str::of($key->keyword)->lower()->startsWith('brickowl')
                )
                ->pluck('id');
            if ($okws->isNotEmpty()) {
                $this->keywords()->detach($okws->all());
                $this->load('keywords');
            }
            $kws = $this->keywords->pluck('keyword')->all();

            if ($this->rebrickable_part->number != $part_num) {
                $kws[] = "Rebrickable {$this->rebrickable_part->number}";
            }
            $bl = $this->getExternalSiteNumber(ExternalSite::BrickLink);
            if (!is_null($bl) && $bl != $part_num) {
                $kws[] = "BrickLink {$bl}";
            }
            $this->setKeywords($kws);
            $this->load('keywords');
            $this->generateHeader();
        }
    }

    public function setHelp(array|SupportCollection $help): void
    {
        $this->help()->delete();
        if (is_array($help)) {
            $help = collect($help);
        } elseif ($help instanceof Collection) {
            $help = collect($help
                ->map(fn (PartHelp $h) =>
                    ['order' => $h->order, 'text' => $h->text]
                )
                ->all()
            );
        }
        $help
            ->each(function (array|string $h, int $key): void {
                if (is_string ($h)) {
                    $help = [
                        'order' => $key,
                        'text' => $h,
                    ];
                } else {
                    $help = [
                        'order' => Arr::has($h, 'order') ? $h['order'] : $key,
                        'text' => $h['text']
                    ] ;
                }
                $this->help()->create($help);
            });
    }

    public function setHistory(array|SupportCollection $history): void
    {
        $this->history()->delete();
        if (is_array($history)) {
            $history = collect($history);
        } elseif ($history instanceof Collection) {
            $history = collect($history
                ->map(fn (PartHistory $h) =>
                    [
                        'user' => $h->user->name,
                        'date' => $h->created_at,
                        'comment' => $h->comment,
                    ]
                )
                ->all()
            );
        }
        $history
            ->each(fn (array $h, int $key) =>
                $this->history()->create([
                    'user_id' =>
                        $h['user'] instanceof User ? $h['user']->id : User::fromAuthor($h['user'])->first()?->id,
                    'created_at' => $h['date'],
                    'comment' => $h['comment']
                ])
        );
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
        $body = $body instanceof PartBody ? $body->body : $body;
        if (is_null($this->body)) {
            $this->body()->create([
                'body' => $body
            ]);
            $this->load('body');
            return;
        }
        $this->body->body = $body;
        $this->body->save();
    }

    public function generateHeader(bool $save = true): void
    {
        $header = [];
        $header[] = "0 {$this->description}" ?? '' ;
        $header[] = "0 Name: {$this->name()}" ?? '';
        $header[] = $this->user->toString();

        $typestr = $this->type->ldrawString($this->isUnofficial());
        if (!is_null($this->type_qualifier)) {
            $typestr .= " {$this->type_qualifier->value}";
        }
        if (!is_null($this->release)) {
            $typestr .= $this->release->toString();
        }
        $header[] = $typestr;
        $header[] = $this->license->ldrawString();
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
        if (!is_null($this->category) && $this->type->inPartsFolder()) {
            $word = 1;
            if (Str::of($this->description)->trim()->words(1,'')->replace(['~', '|', '=', '_'], '') == '') {
                $word = 2;
            }
            $cat = Str::of($this->description)->trim()->words($word,'')->replace(['~', '|', '=', '_', ' '], '')->toString();
            $cat = PartCategory::tryFrom($cat);
            if ($cat != $this->category) {
                $header[] = $this->category->ldrawString();
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

        if (!is_null($this->preview) && $this->preview != '16 0 0 0 1 0 0 0 1 0 0 0 1') {
            $header[] = "0 !PREVIEW {$this->preview}";
            $header[] = '';
        }

        if ($this->history->count() > 0) {
            foreach ($this->history as $h) {
                $header[] = $h->toString();
            }
        }

        $this->header = implode("\n", $header);
        if ($save) {
            $this->saveQuietly();
        }
    }

    public function putDeletedBackup(): void
    {
        $t = time();
        Storage::disk('local')->put("deleted/library/{$this->filename}.{$t}", $this->get());
        Storage::disk('local')->put('deleted/library/' . str_replace(['.png', '.dat'], '.evt', $this->filename). ".{$t}", $this->events->toJson());
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
            return $this->part_status->label();
        }
    }
}
