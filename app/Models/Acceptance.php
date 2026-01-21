<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Acceptance extends Model
{
    use HasFactory, Auditable;

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
        'double_tests',
        'user_id',
        'modification_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'plates' => 'array',
        'tests' => 'array',
        'double_tests' => 'array',
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

    /**
     * Get the Test B result associated with the acceptance.
     */
    public function testBResult(): HasOne
    {
        return $this->hasOne(TestBResult::class);
    }
}