<?php

namespace App\Models\Part;

use App\Collections\PartCollection;
use App\Enums\EventType;
use App\Enums\ExternalSite;
use App\Enums\License;
use App\Enums\PartCategory;
use App\Enums\PartStatus;
use App\Enums\PartType;
use App\Enums\PartTypeQualifier;
use App\Enums\VoteType;
use App\Models\RebrickablePart;
use App\Models\StickerSheet;
use App\Models\Traits\HasErrorScopes;
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
use App\Services\Check\CheckMessageCollection;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasGraphRelationships;
use Znck\Eloquent\Traits\BelongsToThrough as BelongsToThroughTrait;

/**
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Part official()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Part unofficial()
 */
#[ObservedBy([PartObserver::class])]
#[CollectedBy(PartCollection::class)]
class Part extends Model implements HasMedia
{
    use InteractsWithMedia;
    use PivotEventTrait;
    use HasGraphRelationships;
    use BelongsToThroughTrait;
    use HasPartRelease;
    use HasUser;
    use HasFactory;
    use HasErrorScopes;

    protected $guarded = [];

    /**
    * @return array{
    *     'type': 'App\\Enums\\PartType',
    *     'type_qualifier': 'App\\Enums\\PartTypeQualifier',
    *     'license': 'App\\Enums\\License',
    *     'part_status': 'App\\Enums\\PartStatus',
    *     'category': 'App\\Enums\\PartCategory',
    *     'delete_flag': 'boolean',
    *     'manual_hold_flag': 'boolean',
    *     'has_minor_edit': 'boolean',
    *     'missing_parts': 'array',
    *     'can_release': 'boolean',
    *     'marked_for_release': 'boolean',
    *     'is_pattern': 'boolean',
    *     'is_composite': 'boolean',
    *     'is_dual_mould': 'boolean',
    *     'ready_for_admin': 'boolean',
    *     'rebrickable': 'Illuminate\Database\Eloquent\Casts\AsArrayObject',
    *     'help': 'array'
    * }
    */
    protected function casts(): array
    {
        return [
            'type' => PartType::class,
            'type_qualifier' => PartTypeQualifier::class,
            'license' => License::class,
            'part_status' => PartStatus::class,
            'category' => PartCategory::class,
            'delete_flag' => 'boolean',
            'manual_hold_flag' => 'boolean',
            'has_minor_edit' => 'boolean',
            'missing_parts' => 'array',
            'can_release' => 'boolean',
            'marked_for_release' => 'boolean',
            'is_pattern' => 'boolean',
            'is_composite' => 'boolean',
            'is_dual_mould' => 'boolean',
            'ready_for_admin' => 'boolean',
            'rebrickable' => AsArrayObject::class,
            'help' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->keepOriginalImageFormat()
                    ->fit(Fit::Contain, 35, 75);
                $this->addMediaConversion('feed-image')
                    ->keepOriginalImageFormat()
                    ->fit(Fit::Contain, 85, 85);
            });
    }

    public function getCustomPaths()
    {
        return [
            [
                'name' => 'tree_path',
                'column' => 'id',
                'separator' => '.children.',
            ],
        ];
    }
  
    public function newCollection(array $models = []): PartCollection
    {
        return new PartCollection($models);
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

    public function rebrickable_part(): BelongsTo
    {
        return $this->belongsTo(RebrickablePart::class, 'rebrickable_part_id', 'id');
    }

    #[Scope]
    protected function byName(Builder $query, string $name): void
    {
        $name = str_replace('\\', '/', $name);
        if (pathinfo($name, PATHINFO_EXTENSION) == "png") {
            $name = "textures/{$name}";
        }

        $query->where(function ($q) use ($name) {
            $q->orWhere('filename', "p/{$name}")->orWhere('filename', "parts/{$name}");
        });
    }

    #[Scope]
    protected function searchHeader(Builder $query, string $search): void
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
                $query->whereLike('header', "%{$term}%");
            }
        }
    }

    #[Scope]
    protected function partsFolderOnly(Builder $query): void
    {
        $query->whereIn('type', PartType::partsFolderTypes());
    }

    #[Scope]
    protected function canHaveRebrickablePart(Builder $query): void
    {
        $query->partsFolderOnly()
            ->activeParts()
            ->where(
                fn (Builder $query2) =>
                $query2->orWhereNull('type_qualifier')->orWhere('type_qualifier', PartTypeQualifier::Alias)
            )
            ->whereNotLike('description', '~%')
            ->whereNotLike('description', '|%');
    }

    #[Scope]
    protected function activeParts(Builder $query): void
    {
        $query->whereNotIn('category', [PartCategory::Obsolete, PartCategory::Moved]);
    }

    protected function checkMessages(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => CheckMessageCollection::fromArray(json_decode($value ?? '[]', true)),
            set: fn (CheckMessageCollection $value) => json_encode($value->toArray())
        );
    }

    protected function metaName(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::of($this->filename)
                ->replaceMatches('/^(parts\/|p\/)/i', '')
                ->replace('/', '\\')
                ->toString()
        );
    }

    public function hasMessages(): bool
    {
        return $this->tracker_holds->isNotEmpty() || $this->errors->isNotEmpty() || $this->warnings->isNotEmpty();
    }

    public function uncertified_subparts(): Collection
    {
        return $this
            ->descendants()
            ->whereIn('part_status', [PartStatus::AwaitingAdminReview, PartStatus::Needs2MoreVotes, PartStatus::Needs1MoreVote, PartStatus::ErrorsFound])
            ->get();
    }

    public function isText(): bool
    {
        return $this->type->isDatFormat();
    }

    public function isTexmap(): bool
    {
        return $this->type->isImageFormat();
    }

    public function isUnofficial(): bool
    {
        return is_null($this->part_release_id);
    }

    public function isOfficial(): bool
    {
        return !$this->isUnofficial();
    }

    public function isObsolete(): bool
    {
        return $this->category == PartCategory::Obsolete ||
            Str::of($this->description)->contains('(Obsolete)') ||
            Str::of($this->description)->startsWith('~Obsolete');
    }

    public function isFix(): bool
    {
        return $this->isUnofficial() && !is_null($this->official_part);
    }

    public function isNotFix(): bool
    {
        return is_null($this->official_part);
    }

    public function hasFix(): bool
    {
        return $this->isOfficial() && !is_null($this->unofficial_part);
    }

    public function doesntHaveFix(): bool
    {
        return is_null($this->unofficial_part);
    }

    public function canSetRebrickablePart(): bool
    {
        return $this->type->inPartsFolder() &&
            !$this->isObsolete() &&
            $this->category != PartCategory::Moved &&
            $this->type_qualifier !== PartTypeQualifier::FlexibleSection &&
            $this->type_qualifier !== PartTypeQualifier::PhysicalColour &&
            !Str::of($this->description)->startsWith('~') &&
            !Str::of($this->description)->startsWith('|');
    }

    public function getExternalSiteNumber(ExternalSite $external): ?string
    {
        $rbPart = $this->rebrickable_part;
        if (is_null($rbPart)) {
            $match = $this->keywords->first(function (PartKeyword $kw) use ($external) {
                return Str::of($kw->keyword)->lower()
                    ->startsWith(strtolower($external->value) . ' ');
            });
    
            if (!$match) {
                return null;
            }
    
            $num = trim(Str::after(
                strtolower($match->keyword),
                strtolower($external->value . ' ')
            ));
    
            return $num === '' ? null : $num;
        }
        if ($external === ExternalSite::Rebrickable) {
            return $rbPart->number;
        }
      
        $name = $rbPart->ldraw_number ?? basename($this->filename, '.dat');
        $siteList = ($rbPart->{$external->value}) ?? [];
        $number = collect($siteList)->first(function (string $siteNumber) use ($name) {
            return $siteNumber === $name;
        });
        return $number ?: Arr::first($siteList);
    }

    public function lastChange(): Carbon
    {
        if ($this->isOfficial()) {
            $recent_change = $this->release->created_at;
        } else {
            $most_recent_event = $this->events()
                ->unofficial()
                ->whereIn(
                    'event_type',
                    [EventType::Submit, EventType::Rename, EventType::HeaderEdit, EventType::Release]
                )
                ->latest()
                ->first();
            $recent_change = $most_recent_event?->created_at ?? $this->created_at;
        }
        // Zip files only save in 2 sec, even increments
        // This ensures consistancy in time reporting
        if ($recent_change->format('U') % 2 == 1) {
            return $recent_change->subSecond();
        }
        return $recent_change;
    }

    public function libFolder(): string
    {
        return $this->isUnofficial() ? 'unofficial' : 'official';
    }


    public function orderedEvents(): Collection
    {
        return $this->events->sortBy([
            ['created_at', 'asc'],
            fn (PartEvent $a, PartEvent $b) => ($a->event_type == EventType::Submit ? 0 : 1) <=> ($b->event_type == EventType::Submit ? 0 : 1)
        ]);
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
            [VoteType::AdminReview->value => 0, VoteType::Certify->value => 0, VoteType::Hold->value => 0, VoteType::AdminFastTrack->value => 0],
            $this->votes->countBy('vote_type')->all()
        );
    }

    public function updatePartStatus(): void
    {
        if ($this->isOfficial()) {
            $this->part_status = PartStatus::Official;
            $this->saveQuietly();
            return;
        }
        $old_sort = $this->part_status;
        $data = $this->voteTypeCount();

        if ($data[VoteType::Hold->value] != 0) {
            $this->part_status = PartStatus::ErrorsFound;
        } elseif (($data[VoteType::Certify->value] + $data[VoteType::AdminReview->value] < 1) && $data[VoteType::AdminFastTrack->value] == 0) {
            $this->part_status = PartStatus::Needs2MoreVotes;
        } elseif (($data[VoteType::Certify->value] + $data[VoteType::AdminReview->value] < 2) && $data[VoteType::AdminFastTrack->value] == 0) {
            $this->part_status = PartStatus::Needs1MoreVote;
        } elseif ($data[VoteType::AdminFastTrack->value] == 0 && $data[VoteType::AdminReview->value] == 0 && $data[VoteType::Certify->value] >= 2) {
            $this->part_status = PartStatus::AwaitingAdminReview;
        } elseif (($data[VoteType::AdminReview->value] > 0 && ($data[VoteType::Certify->value] + $data[VoteType::AdminReview->value]) > 2) || $data[VoteType::AdminFastTrack->value] > 0) {
            $this->part_status = PartStatus::Certified;
        }
        if ($old_sort == PartStatus::Certified && $this->part_status != PartStatus::Certified) {
            $this->marked_for_release = false;
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
        if ($this->isOfficial()) {
            $this->ready_for_admin = true;
            $this->saveQuietly();
            return;
        }
        $old = $this->ready_for_admin;
        $this->ready_for_admin =
            in_array($this->part_status, [PartStatus::Certified, PartStatus::AwaitingAdminReview]) &&
            !$this->descendants()->whereIn('part_status', [PartStatus::Needs1MoreVote, PartStatus::Needs2MoreVotes, PartStatus::ErrorsFound])->exists();
        if ($old != $this->ready_for_admin) {
            $this->saveQuietly();
            $this->ancestors->unique()->unofficial()->each(function (Part $p) {
                $p->ready_for_admin =
                    in_array($p->part_status, [PartStatus::Certified, PartStatus::AwaitingAdminReview]) &&
                    !$p->descendants()->whereIn('part_status', [PartStatus::Needs1MoreVote, PartStatus::Needs2MoreVotes, PartStatus::ErrorsFound])->exists();
                $p->saveQuietly();
            });
        }
    }

    public function setKeywords(array|Collection $keywords): void
    {
        $keywords = collect($keywords);
    
        $keywordIds = $keywords
            ->filter()
            ->map(fn (string $kw) => Str::of($kw)->trim()->squish()->lower()->toString())
            ->filter()
            ->map(function (string $kw): int {
                $stored = Str::of($kw)->ucfirst()->toString();
    
                return PartKeyword::firstOrCreate(
                    ['keyword' => $stored],
                )->id;
            })
            ->unique()
            ->values();
        $this->keywords()->sync($keywordIds->all());
    }
  
    public function setExternalSiteKeywords(bool $updateOfficial = false): void
    {
        $rbPart = $this->rebrickable_part;
        if (is_null($rbPart) || 
            ($this->Official() && !$updateOfficial) || 
            $this->category->isInactive() ||
            ($rbPart->rb_part_category_id == 58 && $this->category == PartCategory::StickerShortcut)
        ) {
            return;
        }

        $partNum = basename($this->filename, '.dat');


        $prefixes = ExternalSite::prefixes();

        $idsToRemove = $this->keywords
            ->filter(fn (PartKeyword $kw) =>
                Str::startsWith(Str::lower($kw->keyword), $prefixes)
            )
            ->pluck('id');

        if ($idsToRemove->isNotEmpty()) {
            $this->keywords()->detach($idsToRemove->all());
            $this->load('keywords');
        }
        
        $keywords = $this->keywords->pluck('keyword')->all();
        $kwSet = false;

        if ($rbPart->number !== $partNum) {
            $keywords[] = ucfirst(ExternalSite::Rebrickable->value) . ' ' . $rbPart->number;
            $kwSet = true;
        }

        foreach (ExternalSite::cases() as $site) {
            if ($site === ExternalSite::Rebrickable) {
                continue;
            }

            $num = $this->getExternalSiteNumber($site);
            if (!is_null($num) && $num !== $partNum) {
                $keywords[] = ucfirst($site->value) . ' ' . $num;
                $kwSet = true;
            }
        }
        
        if ($this->isOfficial() && $kwSet) {
            $this->has_minor_edit = true;
        }
        
        $this->setKeywords($keywords);
        $this->load('keywords');
        $this->generateHeader();
    }

    public function setHistory(array|SupportCollection $history): void
    {
        $this->history()->delete();
    
        $history = collect($history);
    
        if ($history->first() instanceof PartHistory) {
            $history = $history->map(fn (PartHistory $h): array => [
                'username' => $h->user->name,
                'realname' => $h->user->realname,
                'date'     => $h->created_at,
                'comment'  => $h->comment,
            ]);
        }
    
        $history->each(function (array $hist): void {
            $userId = $hist['username']
                ? User::where('name', $hist['username'])->firstOrFail()->id
                : User::where('realname', $hist['realname'])->firstOrFail()->id;
    
            $this->history()->create([
                'user_id'    => $userId,
                'created_at' => $hist['date'],
                'comment'    => $hist['comment'],
            ]);
        });
    }
  
    public function setSubparts(array|Collection $subparts): void
    {
        $currentPartId   = $this->id;
        $currentFilename = $this->filename;
    
        if ($subparts instanceof Collection) {
            $partIds = $subparts
                ->pluck('id')
                ->filter(fn ($id) => $id !== $currentPartId)
                ->all();
    
            $this->subparts()->sync($partIds);
            $this->missing_parts = [];
            $this->save();
            return;
        }
        $subparts = collect($subparts)
            ->filter(fn ($filename) => $filename && $filename !== $currentFilename)
            ->values();
        if ($subparts->isEmpty()) {
            $this->subparts()->sync([]);
            $this->missing_parts = [];
            $this->save();
            return;
        }
    
        $foundParts = Part::whereIn('filename', $subparts)->get();
        
        $partIds = [];
        $missing = [];
    
        $missingMap = [];
        foreach ($subparts as $filename) {
            $logicalName = preg_replace('#^(p/|parts/|p/textures/|parts/textures/)#', '', $filename);
            $missingMap[$logicalName][] = $filename;
        }

        foreach ($missingMap as $logicalName => $paths) {
            $found = false;
            foreach ($paths as $path) {
                if ($foundParts->where('filename', $path)->isNotEmpty()) {
                    $found = true;
                    break;
                }
            }
    
            if (!$found) {
                $missing[] = $logicalName;
            }
        }
    
        $this->subparts()->sync($foundParts->pluck('id'));
        $this->missing_parts = $missing;
        $this->save();
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
        $this->load('user', 'history', 'keywords', 'release');

        $name = $this->meta_name;
        $user = $this->user->toString();
        $license = $this->license->ldrawString();
        $typestr = $this->type->ldrawString($this->isUnofficial());
        if (!is_null($this->type_qualifier)) {
            $typestr .= " {$this->type_qualifier->value}";
        }
        if (!is_null($this->release)) {
            $typestr .= $this->release->toString();
        }

        $help = !is_null($this->help) && count($this->help) > 0 ? '0 !HELP ' . implode("\n0 !HELP ", $this->help) : '';
        $bfc = '';
        if (!is_null($this->bfc)) {
            $bfc = "0 BFC CERTIFY {$this->bfc}";
        } elseif (!$this->isTexmap()) {
            $bfc = "0 BFC NOCERTIFY";
        }

        $category = '';
        $firstWord = Str::of($this->description)
            ->replaceMatches('/^[~|=_]+/i', '')
            ->trim()
            ->words(1, '')
            ->toString();
        if (PartCategory::tryFrom($firstWord) != $this->category && $this->type->inPartsFolder()) {
            $category = $this->category->ldrawString() . "\n";
        }

        $keywords = '';
        $line = '';
        $this->keywords->each(function (PartKeyword $keyword, int $id) use (&$keywords, &$line) {
            $kw = ($line === '') ? $keyword->keyword : ', ' . $keyword->keyword;
            if (Str::length("0 !KEYWORDS {$line}{$kw}") > 80) {
                $keywords .= "\n0 !KEYWORDS {$line}";
                $line = $keyword->keyword;
            } else {
                $line .= $kw;
            }
        });
        $keywords .= $line !== '' ? "\n0 !KEYWORDS {$line}" : '';
      
        $preview = !is_null($this->preview) && $this->preview !== '' ? "0 !PREVIEW {$this->preview}" : '';
        $cmdline = !is_null($this->cmdline) && $this->cmdline !== '' ? "0 !CMDLINE {$this->cmdline}" : '';
        $history = $this->history
              ->map(fn (PartHistory $h): string => $h->toString())
              ->implode("\n");

        $header = "0 {$this->description}\n" .
                  "0 Name: {$name}\n" .
                  "{$user}\n{$typestr}\n{$license}\n\n" .
                  "{$help}\n\n{$bfc}\n\n{$category}\n{$keywords}\n\n" .
                  "{$cmdline}\n\n{$preview}\n\n{$history}";

        $this->header = trim(preg_replace('#\n{3,}#us', "\n\n", $header));
        if ($save) {
            $this->saveQuietly();
        }
        if (config('ldraw.library_debug')) {
            Log::debug("Generated header for {$this->id} ({$this->filename})\n{$this->header}");
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
            foreach ([VoteType::AdminFastTrack->value, VoteType::AdminReview->value, VoteType::Certify->value, VoteType::Hold->value] as $letter) {
                $code .= str_repeat($letter, $codes[$letter]);
            }
            return $code .= is_null($this->official_part) ? 'N)' : 'F)';
        } else {
            return $this->part_status->label();
        }
    }
}
