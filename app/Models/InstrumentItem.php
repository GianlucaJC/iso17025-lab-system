<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrumentItem extends Model
{
    use HasFactory;

    protected $fillable = ['instrument_id', 'identifier', 'description'];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }
}