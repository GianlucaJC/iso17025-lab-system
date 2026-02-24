<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MethodRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'method_key',
        'method_name',
        'revision_string',
    ];
}
