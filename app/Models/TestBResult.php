<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class TestBResult extends Model
{
    use HasFactory, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_b_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'acceptance_id',
        'operator_id',
        'test_start_datetime', // Common for both runs
        'test_end_datetime',   // Common for both runs
        'plate_id_start_run1',
        'plate_id_mid_run1',
        'plate_id_end_run1',
        'incubator_35_run1',
        'incubation_start_datetime_35_run1',
        'incubation_end_datetime_35_run1',
        'temperature_35_run1',
        'growth_result_35_start_run1',
        'growth_result_35_mid_run1',
        'growth_result_35_end_run1',
        'incubator_22_run1',
        'incubation_start_datetime_22_run1',
        'incubation_end_datetime_22_run1',
        'temperature_22_run1',
        'growth_result_22_start_run1',
        'growth_result_22_mid_run1',
        'growth_result_22_end_run1',
        'plate_id_start_run2', // For double test
        'plate_id_mid_run2',   // For double test
        'plate_id_end_run2',   // For double test
        'incubator_35_run2',   // For double test
        'incubation_start_datetime_35_run2',
        'incubation_end_datetime_35_run2',
        'temperature_35_run2',
        'growth_result_35_start_run2',
        'growth_result_35_mid_run2',
        'growth_result_35_end_run2',
        'incubator_22_run2',   // For double test
        'incubation_start_datetime_22_run2',
        'incubation_end_datetime_22_run2',
        'temperature_22_run2',
        'growth_result_22_start_run2',
        'growth_result_22_mid_run2',
        'growth_result_22_end_run2',
        'outcome',
        'non_compliance_ref',
        'notes',
        'modification_reason',
    ];

    protected $casts = [
        'test_start_datetime' => 'datetime',
        'test_end_datetime' => 'datetime',
        'incubation_start_datetime_35_run1' => 'datetime',
        'incubation_end_datetime_35_run1' => 'datetime',
        'incubation_start_datetime_22_run1' => 'datetime',
        'incubation_end_datetime_22_run1' => 'datetime',
        'incubation_start_datetime_35_run2' => 'datetime',
        'incubation_end_datetime_35_run2' => 'datetime',
        'incubation_start_datetime_22_run2' => 'datetime',
        'incubation_end_datetime_22_run2' => 'datetime',
    ];

    /**
     * Get the acceptance that owns the test result.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function acceptance()
    {
        return $this->belongsTo(Acceptance::class);
    }
}