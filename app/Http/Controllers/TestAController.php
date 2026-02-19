<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use App\Models\TestAResult;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestSignedForValidation;
use Illuminate\Http\Client\ConnectionException;
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
        // Gli amministratori (ruolo 1) e i Responsabili Laboratorio (ruolo 4) non possono creare test.
        if (isset($currentUser['user17025']) && ($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4)) {
            return redirect()->route('acceptance.index')->with('error', 'Gli amministratori non possono creare nuovi test.');
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
        return redirect()->route('acceptance.index', ['highlight' => $acceptance->id])->with('success', 'Risultati del Test A salvati con successo!');
    }

    /**
     * Mostra il form per modificare i risultati del Test A.
     *
     * @param  \App\Models\TestAResult  $test_a_result
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(TestAResult $test_a_result)
    {
        //dd($test_a_result); // <--- AGGIUNGI QUESTA RIGA TEMPORANEAMENTE
        
        $currentUser = Session::get('user');
        $isOwner = $test_a_result->operator_id === $currentUser['id'];
        
        $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;
        $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
        // Il form è in sola lettura se l'utente non è il proprietario, o se il test è stato firmato o validato.
        $is_readonly = $isAdmin || $isLabManager || !$isOwner || $test_a_result->lab_signed_at || $test_a_result->rl_signature_id;

        // --- Inizio blocco recupero utenti via API ---
        $usersMap = [];
        try {
            $httpClient = Http::getFacadeRoot();
            if (filter_var(env('API_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN) === false) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $requestBody = [
                'api_token' => env('API_LOGIN_SHARED_SECRET'),
                'action' => 'get_users'
            ];
            if ($currentUser && isset($currentUser['username'])) {
                $requestBody['username'] = $currentUser['username'];
            }

            $usersResponse = $httpClient->post(env('API_LOGIN_URL'), $requestBody);

            if ($usersResponse->successful() && !empty($usersResponse->json('users'))) {
                $usersMap = collect($usersResponse->json('users'))->keyBy('id')->all();
            } else {
                Log::error("API call to get users failed in TestAController@edit with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
            }
        } catch (ConnectionException $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API in TestAController@edit (Connection Error): " . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Errore inatteso durante il recupero della lista utenti dall'API in TestAController@edit: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

        // Carichiamo l'accettazione associata per avere i dati di contesto
        $acceptance = $test_a_result->acceptance;

        return view('tests.test_a.create', [
            'acceptance' => $acceptance,
            'test_a_result' => $test_a_result, // Passiamo il risultato esistente alla vista
            'currentUser' => $currentUser,
            'is_readonly' => $is_readonly,
            'usersMap' => $usersMap,
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
        $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;
        $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
        // 1. Policy di sicurezza: non si può modificare se non si è proprietari o se il test è firmato/validato.
        if (!$isOwner || $test_a_result->lab_signed_at || $test_a_result->rl_signature_id || $isAdmin || $isLabManager) {
            abort(403, 'Azione non autorizzata: il test è firmato o validato e non può essere modificato.');
        }

        // 2. Validazione
        $validatedData = $request->validate([
            'test_date' => 'required|date',
            'ph_value' => 'required|numeric|min:0|max:14',
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
            'modification_reason' => 'required|string|min:10|max:500',
        ]);

        // 3. Aggiorniamo il record esistente
        $test_a_result->update($validatedData);

        // 4. Reindirizziamo all'elenco con un messaggio di successo
        return redirect()->route('acceptance.index', ['highlight' => $test_a_result->acceptance_id])->with('success', 'Risultati del Test A aggiornati con successo!');
    }

    /**
     * Appone la firma del tecnico di laboratorio al test.
     */
    public function sign(Request $request, TestAResult $test_a_result)
    {
        Log::info('TestAController@sign method invoked for TestAResult ID: ' . $test_a_result->id);
        $currentUser = Session::get('user');

        // Policy 1: Solo i tecnici di laboratorio (ruolo 3) possono firmare.
        $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;
        if (!$isLabTechnician) {
            abort(403, 'Azione non autorizzata: solo i tecnici di laboratorio possono firmare i test.');
        }

        // Policy 2: Solo il proprietario del test può firmare.
        if ($test_a_result->operator_id !== $currentUser['id']) {
            abort(403, 'Azione non autorizzata: solo l\'operatore che ha compilato il test può firmare.');
        }

        // Policy 3: Il test non deve essere già firmato o validato.
        if ($test_a_result->lab_signed_at || $test_a_result->rl_signature_id) {
            return redirect()->route('test-a.edit', $test_a_result->id)->with('error', 'Il test è già stato firmato o validato.');
        }

        $test_a_result->lab_signature_id = $currentUser['id'];
        $test_a_result->lab_signed_at = now();
        $test_a_result->save();

        // --- Invia notifica email ai RL ---
        $rlEmails = $this->getRlEmails();
        if (empty($rlEmails)) {
            Session::flash('notification_warning', 'ATTENZIONE: Nessun Responsabile di Laboratorio con email valida trovato. La notifica non è stata inviata.');
        } else {
            $operatorName = $currentUser['operatore'] ?? 'N/D';
            $testType = 'Test A - Controllo pH';
            $acceptance = $test_a_result->acceptance;
            try {
                Mail::to($rlEmails)->send(new TestSignedForValidation($test_a_result, $acceptance, $operatorName, $testType));
                Session::flash('notification_success', 'Notifica di validazione inviata con successo a: ' . implode(', ', $rlEmails));
            } catch (\Exception $e) {
                Log::error("Invio email di notifica fallito per Test A ID {$test_a_result->id}: " . $e->getMessage());
                Session::flash('notification_error', 'ATTENZIONE: La notifica di validazione non è stata inviata. Controllare la configurazione del server di posta. Errore: ' . $e->getMessage());
            }
        }
        // --- Fine notifica ---

        return redirect()->route('acceptance.index', ['highlight' => $test_a_result->acceptance_id])->with('success', 'Test A firmato con successo!');
    }

    /**
     * Valida il test da parte del Responsabile Laboratorio (RL).
     */
    public function validateTest(Request $request, TestAResult $test_a_result)
    {

        // Aggiungiamo un controllo per assicurarci che la validazione provenga dalla pagina di dettaglio del test
        if ($request->input('source') !== 'run_test') {
            abort(403, 'Azione di validazione non permessa da questa pagina.');
        }

        $currentUser = Session::get('user');

        // Policy 1: Solo i Responsabili Laboratorio (ruolo 4) possono validare.
        $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
        if (!$isLabManager) {
            abort(403, 'Azione non autorizzata: solo i Responsabili Laboratorio possono validare i test.');
        }
        // Policy 2: Il test deve essere stato firmato dal tecnico e non ancora validato.
        if (!$test_a_result->lab_signed_at) {
            return redirect()->route('test-a.edit', $test_a_result->id)->with('error', 'Il test non può essere validato perché non è stato ancora firmato dal tecnico.');
        }
        // Policy 3: Il test non deve essere già stato validato.
        if ($test_a_result->rl_signed_at) {
            return redirect()->route('test-a.edit', $test_a_result->id)->with('error', 'Il test è già stato validato.');
        }

        $test_a_result->update(['rl_signature_id' => $currentUser['id'], 'rl_signed_at' => now()]);
        return redirect()->route('acceptance.index', ['highlight' => $test_a_result->acceptance_id])->with('success', 'Test A validato con successo dal Responsabile Laboratorio!');
    }

    /**
     * Recupera gli indirizzi email dei Responsabili di Laboratorio (RL).
     */
    private function getRlEmails(): array
    {
        $rlEmails = [];
        try {
            $httpClient = Http::getFacadeRoot();
            $certPath = env('API_CERT_PATH');

            if ($certPath && file_exists($certPath)) {
                $httpClient = $httpClient->withOptions(['verify' => $certPath]);
            }
            elseif (filter_var(env('API_SSL_VERIFY', true), FILTER_VALIDATE_BOOLEAN) === false) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $usersResponse = $httpClient->post(env('API_LOGIN_URL'), [
                'api_token' => env('API_LOGIN_SHARED_SECRET'),
                'action' => 'get_users'
            ]);

            if ($usersResponse->successful() && !empty($usersResponse->json('users'))) {
                $allUsers = $usersResponse->json('users');
                foreach ($allUsers as $user) {
                    // Role 4 is 'Responsabile Laboratorio'
                    if (isset($user['user17025']) && $user['user17025'] == 4 && !empty($user['email'])) {
                        $rlEmails[] = $user['email'];
                    }
                }
            } else {
                Log::error("API call to get users for email notification failed with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
            }
        } catch (\Exception $e) {
            Log::error("Failed to retrieve RL emails for notification: " . $e->getMessage());
        }
        return array_unique($rlEmails); // Evita duplicati
    }
}