<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmNotification extends Model
{
    use HasFactory;

    protected $table = 'crm_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'message',
    ];

    /**
     * Get the user who received this notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
