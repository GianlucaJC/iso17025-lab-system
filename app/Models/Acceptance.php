<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;

class Acceptance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'acceptance_number',
        'lotto',
        'sampling_date',
        'acceptance_date',
        'plates',
        'tests',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'plates' => 'array',
        'tests' => 'array',
    ];

    /**
     * Get the user that created the acceptance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the Test A result associated with the acceptance.
     */
    public function testAResult(): HasOne
    {
        return $this->hasOne(TestAResult::class);
    }
}