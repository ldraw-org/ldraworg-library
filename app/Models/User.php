<?php

namespace App\Models;

use App\Enums\License;
use App\Models\Mybb\MybbUser;
use App\Models\Part\Part;
use App\Models\Part\PartEvent;
use App\Models\Part\PartHistory;
use App\Models\Part\UnknownPartNumber;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Traits\HasParts;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperUser
 */
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable
{
    use HasFactory;
    use HasParts;
    use HasRoles;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'realname',
        'password',
        'license',
        'forum_user_id',
        'is_legacy',
        'is_synthetic',
        'is_ptadmin'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
    * @return array{
    *     'license': 'App\\Enums\\License',
    *     'email_verified_at': 'datetime',
    *     'profile_settings': 'array',
    *     'is_legacy': 'boolean',
    *     'is_synthetic': 'boolean',
    *     'is_ptadmin': 'boolean',
    *     'ca_confirm': 'boolean',
    * }
    */
    protected function casts(): array
    {
        return [
            'license' => License::class,
            'email_verified_at' => 'datetime',
            'profile_settings' => 'array',
            'is_legacy' => 'boolean',
            'is_synthetic' => 'boolean',
            'is_ptadmin' => 'boolean',
            'ca_confirm' => 'boolean'
        ];
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function part_events(): HasMany
    {
        return $this->hasMany(PartEvent::class);
    }

    public function part_history(): HasMany
    {
        return $this->hasMany(PartHistory::class);
    }

    public function unknown_part_numbers(): HasMany
    {
        return $this->hasMany(UnknownPartNumber::class);
    }

    public function notification_parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class, 'user_part_notifications');
    }

    public function forum_user(): BelongsTo
    {
        return $this->belongsTo(MybbUser::class, 'forum_user_id', 'uid');
    }

    /** @return Attribute<string, never> */
    protected function authorString(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if ($attributes['is_legacy'] === 1) {
                    return $attributes['realname'];
                } elseif ($attributes['is_ptadmin'] === 1) {
                    return "[{$attributes['name']}]";
                } else {
                    return "{$attributes['realname']} [{$attributes['name']}]";
                }
            }
        );
    }

    #[Scope]
    protected function fromAuthor(Builder $query, string $username, ?string $realname = null): void
    {
        $query->where(function (Builder $q) use ($username, $realname) {
            $q->orWhere('realname', $realname)->orWhere('name', $username);
        });
    }

    public function historyString(): string
    {
        if ($this->is_synthetic === true) {
            return "{{$this->realname}}";
        }
        if ($this->is_legacy === true) {
            return "{{$this->name}}";
        }

        return "[{$this->name}]";
    }

    public function toString(): string
    {
        return "0 Author: " . $this->author_string;
    }

}
