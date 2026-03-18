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

        // Run 1 - 35C Incubation Plates
        'plate_id_start_plate1_35_run1',
        'plate_id_start_plate2_35_run1',
        'plate_id_mid_plate1_35_run1',
        'plate_id_mid_plate2_35_run1',
        'plate_id_end_plate1_35_run1',
        'plate_id_end_plate2_35_run1',
        'incubator_35_run1',
        'incubation_start_datetime_35_run1',
        'incubation_end_datetime_35_run1',
        'temperature_35_run1',
        'growth_result_35_start_plate1_run1', // Growth results for 35C
        'growth_result_35_start_plate2_run1',
        'growth_result_35_mid_plate1_run1',
        'growth_result_35_mid_plate2_run1',
        'growth_result_35_end_plate1_run1',
        'growth_result_35_end_plate2_run1',

        // Run 1 - 22C Incubation Plates
        'plate_id_start_plate1_22_run1',
        'plate_id_start_plate2_22_run1',
        'plate_id_mid_plate1_22_run1',
        'plate_id_mid_plate2_22_run1',
        'plate_id_end_plate1_22_run1',
        'plate_id_end_plate2_22_run1',
        'incubator_22_run1',
        'incubation_start_datetime_22_run1',
        'incubation_end_datetime_22_run1',
        'temperature_22_run1',
        'growth_result_22_start_plate1_run1', // Growth results for 22C
        'growth_result_22_start_plate2_run1',
        'growth_result_22_mid_plate1_run1',
        'growth_result_22_mid_plate2_run1',
        'growth_result_22_end_plate1_run1',
        'growth_result_22_end_plate2_run1',

        // Run 2 - 35C Incubation Plates (For double test)
        'plate_id_start_plate1_35_run2',
        'plate_id_start_plate2_35_run2',
        'plate_id_mid_plate1_35_run2',
        'plate_id_mid_plate2_35_run2',
        'plate_id_end_plate1_35_run2',
        'plate_id_end_plate2_35_run2',
        'incubator_35_run2',   // For double test
        'incubation_start_datetime_35_run2',
        'incubation_end_datetime_35_run2',
        'temperature_35_run2',
        'growth_result_35_start_plate1_run2', // Growth results for 35C
        'growth_result_35_start_plate2_run2',
        'growth_result_35_mid_plate1_run2',
        'growth_result_35_mid_plate2_run2',
        'growth_result_35_end_plate1_run2',
        'growth_result_35_end_plate2_run2',

        // Run 2 - 22C Incubation Plates (For double test)
        'plate_id_start_plate1_22_run2',
        'plate_id_start_plate2_22_run2',
        'plate_id_mid_plate1_22_run2',
        'plate_id_mid_plate2_22_run2',
        'plate_id_end_plate1_22_run2',
        'plate_id_end_plate2_22_run2',
        'incubator_22_run2',   // For double test
        'incubation_start_datetime_22_run2',
        'incubation_end_datetime_22_run2',
        'temperature_22_run2',
        'growth_result_22_start_plate1_run2', // Growth results for 22C
        'growth_result_22_start_plate2_run2',
        'growth_result_22_mid_plate1_run2',
        'growth_result_22_mid_plate2_run2',
        'growth_result_22_end_plate1_run2',
        'growth_result_22_end_plate2_run2',
        'productivity_result',
        'outcome',
        'non_compliance_ref',
        'notes',
        'modification_reason',
        'lab_signature_id',
        'lab_signed_at',
        'rl_signature_id', // Aggiunto per la validazione del Responsabile Laboratorio
        'rl_signed_at',    // Aggiunto per la data di validazione
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
        'lab_signed_at' => 'datetime',
        'rl_signed_at' => 'datetime',
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