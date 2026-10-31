<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequestFile extends Model
{
    use HasFactory;

    protected $table = 'overtime_request_files';

    protected $fillable = [
        'overtime_request_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
    ];

    public function overtimeRequest(): BelongsTo
    {
        return $this->belongsTo(OvertimeRequest::class, 'overtime_request_id');
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

