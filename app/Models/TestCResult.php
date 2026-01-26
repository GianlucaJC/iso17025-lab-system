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
        'plate_id_1',
        'plate_id_2',
        'plate_id_3',
        'plate_id_control_blank',
        'plate_id_control_tsa',
        'growth_result_plate_1',
        'growth_result_plate_2',
        'growth_result_plate_3',
        'growth_result_control_blank',
        'growth_result_control_tsa',
        'plate_id_1_run2',
        'plate_id_2_run2',
        'plate_id_3_run2',
        'plate_id_control_blank_run2',
        'plate_id_control_tsa_run2',
        'growth_result_plate_1_run2',
        'growth_result_plate_2_run2',
        'growth_result_plate_3_run2',
        'growth_result_control_blank_run2',
        'growth_result_control_tsa_run2',
        'outcome',
        'non_compliance_ref',
        'notes',
        'modification_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'test_start_datetime' => 'datetime',
        'test_end_datetime' => 'datetime',
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