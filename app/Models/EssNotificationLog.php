<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EssNotificationLog extends Model
{
    use HasFactory;

    protected $table = 'ess_notification_logs';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'event',
        'receiver_name',
        'receiver_phone',
        'message',
        'status',
        'response_payload',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }
}
