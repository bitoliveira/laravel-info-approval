<?php

namespace bitoliveira\Approval\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Approval extends Model
{
    use SoftDeletes;

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

    // Relationships

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    // Query Scopes

    /**
     * Scope a query to only include pending approvals.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved approvals.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected approvals.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by approvable type.
     */
    public function scopeForType(Builder $query, string $type): Builder
    {
        return $query->where('approvable_type', $type);
    }

    /**
     * Scope a query to filter by approvable model.
     */
    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('approvable_type', get_class($model))
            ->where('approvable_id', $model->getKey());
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by requester.
     */
    public function scopeRequestedBy(Builder $query, int $userId): Builder
    {
        return $query->where('requested_by', $userId);
    }

    /**
     * Scope a query to filter by approver.
     */
    public function scopeApprovedBy(Builder $query, int $userId): Builder
    {
        return $query->where('approved_by', $userId);
    }

    /**
     * Scope a query to filter by current level.
     */
    public function scopeAtLevel(Builder $query, int $level): Builder
    {
        return $query->where('current_level', $level);
    }

    /**
     * Scope a query to order by most recent.
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->latest();
    }
}
