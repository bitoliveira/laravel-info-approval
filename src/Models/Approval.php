<?php

namespace bitoliveira\Approval\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $table = 'approvals';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'action',
        'data',
        'levels',
        'current_level',
        'approvals_log',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'levels' => 'array',
        'approvals_log' => 'array',
        'approved_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }
}
