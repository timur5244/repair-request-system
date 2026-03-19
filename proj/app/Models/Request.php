<?php

namespace App\Models;

use App\Domain\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'clientName',
        'phone',
        'address',
        'problemText',
        'status',
        'assignedTo',
    ];

    protected $casts = [
        'status' => RequestStatus::class,
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignedTo');
    }
}
