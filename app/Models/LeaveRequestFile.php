<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequestFile extends Model
{
    use HasFactory;

    protected $table = 'leave_request_files';

    protected $fillable = [
        'leave_request_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'leave_request_id');
    }

    /**
     * URL file: langsung generate dari IS3 (path-style).
     * Tidak pakai exists() untuk hindari extra HTTP request yang lambat/gagal.
     * Fallback ke public/local jika file_path terdeteksi bukan dari IS3.
     */
    public function getUrlAttribute(): string
    {
        if (!$this->file_path) {
            return '';
        }

        // Jika path dimulai dari "leave_attachments/" atau path cloud — langsung IS3
        try {
            return \Illuminate\Support\Facades\Storage::disk('is3')->url($this->file_path);
        } catch (\Throwable $e) {
            // IS3 config belum ada / gagal — fallback ke public
        }

        return asset('storage/' . $this->file_path);
    }
}

