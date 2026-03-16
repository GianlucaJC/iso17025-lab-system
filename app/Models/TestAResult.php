<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class TestAResult extends Model
{
    use HasFactory, Auditable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'test_a_results';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'acceptance_id',
        'test_date',
        'operator_id',
        'ph_meter',
        'ph_probe',
        'ph_value',
        'ph_meter_double',
        'ph_probe_double',
        'ph_value_double',
        'outcome',
        'non_compliance_ref',
        'rl_signature_id', // Aggiunto per la validazione del Responsabile Laboratorio
        'rl_signed_at',    // Aggiunto per la data di validazione
        'modification_reason',
    ];

    /**
     * Get the acceptance that owns the test result.
     */
    public function acceptance()
    {
        return $this->belongsTo(Acceptance::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'lab_signed_at' => 'datetime',
        'rl_signed_at' => 'datetime',
    ];
}
