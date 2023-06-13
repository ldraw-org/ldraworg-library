<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartEvent extends Model
{
    protected $fillable = [
      'created_at',
      'initial_submit',
      'part_id',
      'user_id',
      'vote_type_code',
      'part_release_id',
      'part_event_type_id',
      'comment',
      'deleted_filename',
      'deleted_description',
      'moved_from_filename',
      'initial_submit',
    ];

    protected $casts = [
      'initial_submit' => 'boolean',
    ];
  
    public function part_event_type() {
      return $this->belongsTo(PartEventType::class);
    }
    
    public function user() {
      return $this->belongsTo(User::class);
    }

    public function vote_type() {
      return $this->belongsTo(VoteType::class);
    }

    public function part() {
      return $this->belongsTo(Part::class);
    }
    
    public function release() {
      return $this->belongsTo(PartRelease::class, 'part_release_id');
    }
}
