<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestAResult extends Model
{
    use HasFactory;

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
        'ph_value',
        'outcome',
        'non_compliance_ref',
    ];

    /**
     * Get the acceptance that owns the test result.
     */
    public function acceptance()
    {
        return $this->belongsTo(Acceptance::class);
    }
}
