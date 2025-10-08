<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'subject',
        'message',
        'questions',
        'status',
        'admin_notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the files attached to this contact message
     */
    public function files(): HasMany
    {
        return $this->hasMany(ContactFile::class);
    }

    /**
     * Get safe files only
     */
    public function safeFiles(): HasMany
    {
        return $this->hasMany(ContactFile::class)->where('is_safe', true);
    }

    /**
     * Check if this message has attachments
     */
    public function getHasAttachmentsAttribute(): bool
    {
        return $this->files()->count() > 0;
    }

    /**
     * Get count of attachments
     */
    public function getAttachmentsCountAttribute(): int
    {
        return $this->files()->count();
    }
}
