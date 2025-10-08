<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section',
        'type',
        'title',
        'description',
        'video_url',
        'video_id',
        'thumbnail',
        'link_url',
        'sort_order',
        'is_active',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
        'sort_order' => 'integer'
    ];

    /**
     * Scope to get active content
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get content by section
     */
    public function scopeBySection($query, $section)
    {
        return $query->where('section', $section);
    }

    /**
     * Scope to order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Extract YouTube video ID from URL
     */
    public function extractYouTubeId($url)
    {
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Set video URL and automatically extract video ID
     */
    public function setVideoUrlAttribute($value)
    {
        $this->attributes['video_url'] = $value;
        
        if ($value && strpos($value, 'youtube') !== false) {
            $this->attributes['video_id'] = $this->extractYouTubeId($value);
        }
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute()
    {
        if ($this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        
        if ($this->video_id) {
            return "https://img.youtube.com/vi/{$this->video_id}/maxresdefault.jpg";
        }
        
        return null;
    }

    /**
     * Get embed URL for YouTube videos
     */
    public function getEmbedUrlAttribute()
    {
        if ($this->video_id) {
            return "https://www.youtube.com/embed/{$this->video_id}?controls=1&modestbranding=1&rel=0&showinfo=0";
        }
        
        return $this->video_url;
    }
}
