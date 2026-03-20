<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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

    /**
     * Checks if an Acceptance record is complete for PDF generation.
     * An acceptance is considered complete if all its required tests have been validated by the RL.
     * This method does NOT consider the `annulled_at` status.
     *
     * @return bool
     */
    public function isPdfComplete(): bool
    {
        // If the sample is non-conforming, no tests are required, so the PDF is considered complete.
        if ($this->sample_conformity === 'non_conforme') {
            return true;
        }

        $requiredTests = $this->tests ?? [];

        // If the sample is conforming but no tests were selected, it's not complete.
        if (empty($requiredTests) && $this->sample_conformity === 'conforme') {
            return false;
        }

        // Eager load relationships if not already loaded
        $this->loadMissing('testAResult', 'testBResult', 'testCResult');

        foreach ($requiredTests as $testKey) {
            if ($testKey === 'test1' && (!$this->testAResult || !$this->testAResult->rl_signed_at)) return false;
            if ($testKey === 'test2' && (!$this->testBResult || !$this->testBResult->rl_signed_at)) return false;
            if ($testKey === 'test3' && (!$this->testCResult || !$this->testCResult->rl_signed_at)) return false;
        }

        return true; // All required tests are present and validated
    }

    public function checkAndClearAnnulmentIfRevalidated(): void
    {
        if ($this->annulled_at && $this->isPdfComplete()) {
            $this->update(['annulled_at' => null, 'annulment_reason' => null]);
            Log::info("Acceptance ID {$this->id} annulment cleared due to full re-validation.");
        }
    }
}