<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AcceptanceController extends Controller
{
    /**
     * Mostra il form per creare una nuova accettazione.
     */
    public function create()
    {
        return view('create');
    }
}
