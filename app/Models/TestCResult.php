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
        'salmonella_typhimurium_plate_lot',
        'incubator',
        'incubation_start_datetime',
        'incubation_end_datetime',
        'temperature',
        'tsa_growth_ufc', // New
        'tsa_growth_result',
        'plate_id_start_lotto', // Existing
        'growth_result_start_lotto',
        'ufc_start_lotto', // New
        'ufc_50_percent_tsa_start_lotto', // New
        'plate_id_mid_lotto', // Existing
        'growth_result_mid_lotto',
        'ufc_mid_lotto', // New
        'ufc_50_percent_tsa_mid_lotto', // New
        'plate_id_end_lotto', // Existing
        'growth_result_end_lotto',
        'ufc_end_lotto', // New
        'ufc_50_percent_tsa_end_lotto', // New
        'plate_id_control_blank', // Existing
        'growth_result_control_blank',
        'tsa_sheep_blood_plate_lot', // Keep this one
        'tsa_sheep_blood_plate_id_run2',
        'tsa_sheep_blood_plate_lot_run2', // New
        'pipette_dilution_1_run2',
        'pipette_dilution_2_run2',
        'pipette_inoculation_run2',
        'salmonella_typhimurium_plate_lot_run2',
        'incubator_run2',
        'incubation_start_datetime_run2',
        'incubation_end_datetime_run2',
        'temperature_run2',
        'tsa_growth_ufc_run2', // New
        'tsa_growth_result_run2',
        'plate_id_start_lotto_run2', // Existing
        'growth_result_start_lotto_run2',
        'ufc_start_lotto_run2', // New
        'ufc_50_percent_tsa_start_lotto_run2', // New
        'plate_id_mid_lotto_run2', // Existing
        'growth_result_mid_lotto_run2',
        'ufc_mid_lotto_run2', // New
        'ufc_50_percent_tsa_mid_lotto_run2', // New
        'plate_id_end_lotto_run2', // Existing
        'ufc_end_lotto_run2', // New
        'ufc_50_percent_tsa_end_lotto_run2', // New
        'growth_result_control_blank_run2',
        'productivity_result',
        'outcome',
        'non_compliance_ref',
        'notes',
        'modification_reason',
        'lab_signature_id',
        'lab_signed_at',
        'rl_signature_id',
        'rl_signed_at',
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
        'ufc_50_percent_tsa_start_lotto' => 'boolean', // New
        'ufc_50_percent_tsa_mid_lotto' => 'boolean', // New
        'ufc_50_percent_tsa_end_lotto' => 'boolean', // New
        'ufc_50_percent_tsa_start_lotto_run2' => 'boolean', // New
        'ufc_50_percent_tsa_mid_lotto_run2' => 'boolean', // New
        'ufc_50_percent_tsa_end_lotto_run2' => 'boolean', // New
        'tsa_growth_ufc' => 'integer', // New
        'ufc_start_lotto' => 'integer', // New
        'ufc_mid_lotto' => 'integer', // New
        'ufc_end_lotto' => 'integer', // New
        'tsa_growth_ufc_run2' => 'integer', // New
        'ufc_start_lotto_run2' => 'integer', // New
        'ufc_mid_lotto_run2' => 'integer', // New
        'ufc_end_lotto_run2' => 'integer', // New
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