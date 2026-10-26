<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasAuditTrail
{
    /**
     * Boot the audit trail trait for model events.
     */
    public static function bootHasAuditTrail(): void
    {
        static::creating(function ($model): void {
            if (empty($model->created_by) && Auth::check() && in_array('created_by', $model->getFillable(), true)) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model): void {
            if (Auth::check() && in_array('updated_by', $model->getFillable(), true)) {
                $model->updated_by = Auth::id();
            }
        });
    }

    /**
     * Get the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
