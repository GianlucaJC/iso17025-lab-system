<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TestAController extends Controller
{
    /**
     * Mostra il form per inserire i risultati del Test A.
     *
     * @param  \App\Models\Acceptance  $acceptance
     * @return \Illuminate\View\View
     */
    public function create(Acceptance $acceptance)
    {
        // Verifichiamo che il Test A sia stato richiesto per questa accettazione
        if (!in_array('test1', $acceptance->tests)) {
            return redirect()->route('acceptance.index')->with('error', 'Il Test A non è previsto per questa accettazione.');
        }

        $currentUser = Session::get('user');

        return view('tests.test_a.create', [
            'acceptance' => $acceptance,
            'currentUser' => $currentUser
        ]);
    }

    /**
     * Salva i risultati del Test A.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Acceptance  $acceptance
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Acceptance $acceptance)
    {
        // TODO: Implementare la logica di validazione e salvataggio nel database.
        // Per ora, facciamo un dump dei dati per vedere cosa arriva dal form.
        dd($request->all());

        /*
        // Esempio di validazione e salvataggio futuri:
        $validatedData = $request->validate([...]);
        // ... logica per creare il record del test associato all'accettazione ...
        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test A salvati con successo!');
        */
    }
}