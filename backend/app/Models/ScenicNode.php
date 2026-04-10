<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenicNode extends Model
{
    protected $fillable = [
        'scenic_spot_id', 'parent_id', 'baidu_uid',
        'name', 'address', 'lat', 'lng',
        'tag', 'telephone', 'overall_rating',
        'sort_order', 'commentary',
    ];

    public function scenicSpot(): BelongsTo
    {
        return $this->belongsTo(ScenicSpot::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ScenicNode::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ScenicNode::class, 'parent_id')->orderBy('sort_order');
    }

    public function isSubSpot(): bool
    {
        return is_null($this->parent_id);
    }

    public function commentaryPoints(): HasMany
    {
        return $this->hasMany(CommentaryPoint::class)->orderBy('sort_order');
    }
}
