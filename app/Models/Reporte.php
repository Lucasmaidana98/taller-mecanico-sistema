<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Reporte extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'filters',
        'generated_by',
        'generated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the user that generated the report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
