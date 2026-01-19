<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use App\Models\TestAResult;
use Illuminate\Validation\Rule;
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
        // 1. Verifichiamo che il Test A sia previsto e non sia già stato inserito
        if (!in_array('test1', $acceptance->tests)) {
            abort(403, 'Il Test A non è previsto per questa accettazione.');
        }
        // La relazione 'testAResult' deve essere definita nel modello Acceptance
        if ($acceptance->testAResult()->exists()) {
            return redirect()->route('acceptance.index')->with('error', 'I risultati per il Test A di questa accettazione sono già stati inseriti.');
        }

        // 2. Validazione dei dati del form
        $validatedData = $request->validate([
            'test_date' => 'required|date',
            'ph_value' => 'required|numeric|min:0|max:14',
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
        ]);

        // 3. Aggiungiamo i dati non provenienti dal form
        $validatedData['acceptance_id'] = $acceptance->id;
        $validatedData['operator_id'] = Session::get('user')['id'];

        // 4. Creiamo il record nel database
        TestAResult::create($validatedData);

        // 5. Reindirizziamo all'elenco con un messaggio di successo
        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test A salvati con successo!');
    }

    /**
     * Mostra il form per modificare i risultati del Test A.
     *
     * @param  \App\Models\TestAResult  $test_a_result
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(TestAResult $test_a_result)
    {
        $currentUser = Session::get('user');
        $isOwner = $test_a_result->operator_id === $currentUser['id'];

        // Il form è in sola lettura se l'utente non è il proprietario O se il test è già stato validato.
        $is_readonly = !$isOwner || $test_a_result->validator_id;

        // Carichiamo l'accettazione associata per avere i dati di contesto
        $acceptance = $test_a_result->acceptance;

        return view('tests.test_a.create', [
            'acceptance' => $acceptance,
            'test_a_result' => $test_a_result, // Passiamo il risultato esistente alla vista
            'currentUser' => $currentUser,
            'is_readonly' => $is_readonly,
        ]);
    }

    /**
     * Aggiorna i risultati del Test A nel database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TestAResult  $test_a_result
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, TestAResult $test_a_result)
    {
        $currentUser = Session::get('user');
        $isOwner = $test_a_result->operator_id === $currentUser['id'];

        // 1. Policy di sicurezza: non si può modificare se non si è proprietari o se il test è validato.
        if (!$isOwner || $test_a_result->validator_id) {
            abort(403, 'Azione non autorizzata: non sei il proprietario o il test è già stato validato.');
        }

        // 2. Validazione
        $validatedData = $request->validate([
            'test_date' => 'required|date',
            'ph_value' => 'required|numeric|min:0|max:14',
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
        ]);

        // 3. Aggiorniamo il record esistente
        $test_a_result->update($validatedData);

        // 4. Reindirizziamo all'elenco con un messaggio di successo
        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test A aggiornati con successo!');
    }
}