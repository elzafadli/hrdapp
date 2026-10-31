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
     * URL file: otomatis ambil dari cloud storage (is3) atau fallback local storage.
     */
    public function getUrlAttribute(): string
    {
        try {
            if (\Illuminate\Support\Facades\Storage::disk('is3')->exists($this->file_path)) {
                return \Illuminate\Support\Facades\Storage::disk('is3')->url($this->file_path);
            }
        } catch (\Throwable $e) {
            // fallback
        }

        return asset('storage/' . $this->file_path);
    }
}

