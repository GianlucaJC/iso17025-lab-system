<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;


class Acceptance extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    /**
     * The name of the "deleted at" column.
     *
     * @var string
     */
    const DELETED_AT = 'dele'; // Specifica che la colonna per il soft delete è 'dele'

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'acceptance_number',
        'dele', // Aggiunto 'dele' ai fillable per permettere l'assegnazione massiva se necessario
        'lotto',
        'sampling_date',
        'acceptance_date',
        'plates',
        'tests',
        'double_tests',
        'user_id',
        'modification_reason',
        'sample_conformity',
        'non_conformity_reason',
        'annulled_at',
        'annulment_reason'
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
        'dele' => 'datetime', // Indica a Laravel di trattare 'dele' come un oggetto Carbon
        'annulled_at' => 'datetime',
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

    /**
     * Get the Test C result associated with the acceptance.
     */
    public function testCResult(): HasOne
    {
        return $this->hasOne(TestCResult::class);
    }
}