<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class TestCResult extends Model
{
    use HasFactory, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_c_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'acceptance_id',
        'operator_id',
        'test_start_datetime',
        'test_end_datetime',
        'tsa_sheep_blood_plate_id',
        'pipette_dilution_1',
        'pipette_dilution_2',
        'pipette_inoculation',
        'incubator',
        'incubation_start_datetime',
        'incubation_end_datetime',
        'temperature',
        'tsa_growth_result',
        'plate_id_start_lotto',
        'growth_result_start_lotto',
        'plate_id_mid_lotto',
        'growth_result_mid_lotto',
        'plate_id_end_lotto',
        'growth_result_end_lotto',
        'plate_id_control_blank',
        'growth_result_control_blank',
        'tsa_sheep_blood_plate_id_run2',
        'pipette_dilution_1_run2',
        'pipette_dilution_2_run2',
        'pipette_inoculation_run2',
        'incubator_run2',
        'incubation_start_datetime_run2',
        'incubation_end_datetime_run2',
        'temperature_run2',
        'tsa_growth_result_run2',
        'plate_id_start_lotto_run2',
        'growth_result_start_lotto_run2',
        'plate_id_mid_lotto_run2',
        'growth_result_mid_lotto_run2',
        'plate_id_end_lotto_run2',
        'growth_result_end_lotto_run2',
        'plate_id_control_blank_run2',
        'growth_result_control_blank_run2',
        'productivity_result',
        'outcome',
        'non_compliance_ref',
        'notes',
        'modification_reason',
        'rl_signature_id', // Aggiunto per la validazione del Responsabile Laboratorio
        'rl_signed_at',    // Aggiunto per la data di validazione
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'test_start_datetime' => 'datetime',
        'test_end_datetime' => 'datetime',
        'incubation_start_datetime' => 'datetime',
        'incubation_end_datetime' => 'datetime',
        'incubation_start_datetime_run2' => 'datetime',
        'incubation_end_datetime_run2' => 'datetime',
        'lab_signed_at' => 'datetime',
        'rl_signed_at' => 'datetime',
    ];

    /**
     * Get the acceptance that owns the test result.
     */
    public function acceptance()
    {
        return $this->belongsTo(Acceptance::class);
    }
}