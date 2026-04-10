<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentaryPoint extends Model
{
    protected $fillable = [
        'scenic_spot_id', 'scenic_node_id',
        'title', 'content', 'audio_url', 'image_url',
        'lat', 'lng', 'trigger_radius', 'duration',
        'sort_order', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function scenicSpot(): BelongsTo
    {
        return $this->belongsTo(ScenicSpot::class);
    }

    public function scenicNode(): BelongsTo
    {
        return $this->belongsTo(ScenicNode::class);
    }
}
