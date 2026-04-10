<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenicSpot extends Model
{
    protected $fillable = ['name', 'level', 'province', 'address'];

    public function nodes(): HasMany
    {
        return $this->hasMany(ScenicNode::class);
    }

    public function subSpots(): HasMany
    {
        return $this->hasMany(ScenicNode::class)->whereNull('parent_id')->orderBy('sort_order');
    }

    public function commentaryPoints(): HasMany
    {
        return $this->hasMany(CommentaryPoint::class)->orderBy('sort_order');
    }
}
