<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'emp_id',
        'date',
        'check_in_time',
        'check_in_lat',
        'check_in_lng',
        'check_in_distance',
        'check_in_status',
        'check_in_notes',
        'check_in_photo',
        'check_out_time',
        'check_out_lat',
        'check_out_lng',
        'check_out_distance',
        'check_out_status',
        'check_out_notes',
        'check_out_photo',
        'work_duration_minutes',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_lat' => 'decimal:7',
        'check_in_lng' => 'decimal:7',
        'check_in_distance' => 'decimal:2',
        'check_out_lat' => 'decimal:7',
        'check_out_lng' => 'decimal:7',
        'check_out_distance' => 'decimal:2',
        'work_duration_minutes' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmpData::class, 'emp_id');
    }

    /**
     * URL Foto Presensi Masuk
     */
    public function getCheckInPhotoUrlAttribute(): ?string
    {
        if (!$this->check_in_photo) {
            return null;
        }

        if (str_starts_with($this->check_in_photo, 'http://') || str_starts_with($this->check_in_photo, 'https://')) {
            return $this->check_in_photo;
        }

        try {
            return Storage::disk('is3')->url($this->check_in_photo);
        } catch (\Throwable $e) {
            // fallback
        }

        return asset('storage/' . $this->check_in_photo);
    }

    /**
     * URL Foto Presensi Keluar
     */
    public function getCheckOutPhotoUrlAttribute(): ?string
    {
        if (!$this->check_out_photo) {
            return null;
        }

        if (str_starts_with($this->check_out_photo, 'http://') || str_starts_with($this->check_out_photo, 'https://')) {
            return $this->check_out_photo;
        }

        try {
            return Storage::disk('is3')->url($this->check_out_photo);
        } catch (\Throwable $e) {
            // fallback
        }

        return asset('storage/' . $this->check_out_photo);
    }
}
