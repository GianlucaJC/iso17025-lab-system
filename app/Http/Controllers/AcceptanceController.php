<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Import the Log facade
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class AcceptanceController extends Controller
{
    /**
     * Mostra il form per creare una nuova accettazione.
     */
    public function create()
    {
        $currentUser = Session::get('user');
        // Gli amministratori (ruolo 1) e i Responsabili Laboratorio (ruolo 4) non possono creare nuove accettazioni.
        if (isset($currentUser['user17025']) && ($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4)) {
            return redirect()->route('acceptance.index')->with('error', 'Gli amministratori non possono creare nuove accettazioni.');
        }

        return view('acceptance.create', ['currentUser' => $currentUser]);
    }

    /**
     * Mostra l'elenco di tutte le accettazioni.
     */
    public function index(Request $request)
    {
        // --- Inizio blocco recupero utenti via API ---
        $usersMap = [];
        try {
            // Prepara il client HTTP (logica copiata da AuthController per coerenza)
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
                $usersData = $usersResponse->json('users');
                if (!empty($usersData)) {
                    // Crea una mappa 'user_id' => ['operatore' => 'Nome Operatore', ...]
                    $usersMap = collect($usersData)->keyBy('id')->all();
                } else {
                    // API returned successful but 'users' array is empty or not present
                    Log::warning("API call for users was successful but returned empty or missing 'users' data. Response: " . $usersResponse->body());
                }
            } else {
                // API call failed (e.g., 4xx, 5xx status code)
                Log::error("API call to get users failed with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
            }
        } catch (ConnectionException $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API (Connection Error): " . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Errore inatteso durante il recupero della lista utenti dall'API: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

        $acceptancesQuery = Acceptance::query(); // Start with a base query
        
        // Se viene richiesto di evidenziare una riga specifica, ignoriamo i filtri
        // per assicurarci che la riga sia visibile, e resettiamo i valori dei filtri.
        if ($request->has('highlight')) {
            $filterTestAStatus = 'all';
            $filterTestBStatus = 'all';
            $filterTestCStatus = 'all';
        } else {
            // Altrimenti, usiamo i filtri dalla richiesta o i valori di default.
            $filterTestAStatus = $request->input('filter_test_a_status', 'all');
            $filterTestBStatus = $request->input('filter_test_b_status', 'all');
            $filterTestCStatus = $request->input('filter_test_c_status', 'all');
        }
        // Helper function to apply status filters consistently
        $applyTestStatusFilter = function ($query, $status, $relation, $isTwoPhase = false) {
            if ($status === 'not_compiled') {
                $query->whereDoesntHave($relation);
            } elseif ($status === 'validated') {
                $query->whereHas($relation, function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('rl_signature_id')->orWhereNotNull('rl_signed_at');
                    });
                });
            } elseif ($status === 'signed') {
                $query->whereHas($relation, function ($q) {
                    $q->whereNotNull('lab_signed_at')->where(function ($sub) {
                        $sub->whereNull('rl_signature_id')->whereNull('rl_signed_at');
                    });
                });
            } elseif ($isTwoPhase) {
                if ($status === 'in_compilation') {
                    $query->whereHas($relation, function ($q) {
                        $q->whereNull('lab_signed_at')->where(function ($sub) {
                            $sub->whereNull('outcome')->orWhere('outcome', '');
                        });
                    });
                } elseif ($status === 'ready_to_sign') {
                    $query->whereHas($relation, function ($q) {
                        $q->whereNull('lab_signed_at')->where(function ($sub) {
                            $sub->whereNotNull('outcome')->where('outcome', '!=', '');
                        });
                    });
                }
            } else { // Single phase tests (A and C)
                if ($status === 'in_compilation' || $status === 'ready_to_sign') {
                    $query->whereHas($relation, function ($q) {
                        $q->whereNull('lab_signed_at')->where(function ($sub) {
                            $sub->whereNull('rl_signature_id')->whereNull('rl_signed_at');
                        });
                    });
                }
            }
        };

        // Apply filters for Test A
        if ($filterTestAStatus !== 'all') {
            $acceptancesQuery->where(function ($query) use ($applyTestStatusFilter, $filterTestAStatus) {
                $applyTestStatusFilter($query, $filterTestAStatus, 'testAResult');
            });
        }

        // Apply filters for Test B
        if ($filterTestBStatus !== 'all') {
            $acceptancesQuery->where(function ($query) use ($applyTestStatusFilter, $filterTestBStatus) {
                $applyTestStatusFilter($query, $filterTestBStatus, 'testBResult', true);
            });
        }

        // Apply filters for Test C
        if ($filterTestCStatus !== 'all') {
            $acceptancesQuery->where(function ($query) use ($applyTestStatusFilter, $filterTestCStatus) {
                $applyTestStatusFilter($query, $filterTestCStatus, 'testCResult', true);
            });
        }


        // Eager load relationships for completeness check in the view
        $acceptancesQuery->with(['testAResult', 'testBResult', 'testCResult']);

        $acceptances = $acceptancesQuery->latest()->get(); // Apply latest() and get() at the end

        // Add is_pdf_complete and detailed test statuses to each acceptance
        $acceptances->each(function ($acceptance) {
            $acceptance->is_pdf_complete = $this->isPdfComplete($acceptance);

            // Helper function to determine status based on the test result record
            $getTestStatus = function ($testResult, $isTwoPhase = false) {
                if (!$testResult) {
                    return 'not_compiled';
                }
                if ($testResult->rl_signed_at ?? $testResult->rl_signature_id) {
                    return 'validated';
                }
                if ($testResult->lab_signed_at) {
                    return 'signed';
                }

                // Per i test a due fasi (come il Test B), se l'esito ('outcome') non è ancora stato compilato,
                // il test è considerato "in compilazione".
                if ($isTwoPhase && empty($testResult->outcome)) {
                    return 'in_compilation';
                }

                // In tutti gli altri casi (test a fase singola o test a due fasi già completati),
                // se il record esiste e non è ancora firmato, è "pronto per la firma".
                return 'ready_to_sign';
            };

            // Determine status for each test
            $acceptance->test_a_status = $getTestStatus($acceptance->testAResult);
            $acceptance->test_b_status = $getTestStatus($acceptance->testBResult, true); // Test B is two-phase
            $acceptance->test_c_status = $getTestStatus($acceptance->testCResult, true); // Test C is now two-phase
        });

        return view('acceptance.index', [
            'acceptances' => $acceptances,
            'currentUser' => Session::get('user'), // Pass currentUser to the view
            'usersMap' => $usersMap,
            'filterTestAStatus' => $filterTestAStatus,
            'filterTestBStatus' => $filterTestBStatus,
            'filterTestCStatus' => $filterTestCStatus,
        ]);
    }

    /**
     * Salva una nuova accettazione nel database.
     */
    public function store(Request $request)
    {
        $rules = [
            'acceptance_number' => 'required|string|max:255|unique:acceptances,acceptance_number',
            'lotto' => 'required|string|max:255',
            'sampling_date' => 'required|date',
            'acceptance_date' => 'required|date',
            'tests' => 'required|array|min:1', // Almeno un test deve essere selezionato
            'sample_conformity' => ['required', Rule::in(['conforme', 'non_conforme'])],
            'non_conformity_reason' => 'required_if:sample_conformity,non_conforme|nullable|string|max:2000',
            'tests.*' => 'string|in:test1,test2,test3', // Valori validi per i test
            'double_tests' => 'nullable|array',
            'double_tests.*' => 'string|in:test1,test2,test3',
        ];

        // Regole di validazione condizionali per gli ID delle piastre
        $selectedTests = $request->input('tests', []);
        $doubleTests = $request->input('double_tests', []);

        // If sample is non-conforming, tests are not required
        $testsRequired = $request->input('sample_conformity') === 'conforme';
        if (!$testsRequired) {
            $rules['tests'] = 'nullable|array';
        }

        // Test A (1 plate, index 0)
        for ($i = 0; $i < 1; $i++) {
            $rules["plates.{$i}"] = (in_array('test1', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test A Doppio (1 plate, index 2)
        for ($i = 2; $i < 3; $i++) {
            $rules["plates.{$i}"] = (in_array('test1', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test B (12 plates, indices 4-15)
        for ($i = 4; $i < 16; $i++) {
            $rules["plates.{$i}"] = (in_array('test2', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test B Doppio (12 plates, indices 16-27)
        for ($i = 16; $i < 28; $i++) {
            $rules["plates.{$i}"] = (in_array('test2', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test C (5 plates, indices 28-31 and 36)
        for ($i = 28; $i < 32; $i++) {
            $rules["plates.{$i}"] = (in_array('test3', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        $rules["plates.36.id"] = (in_array('test3', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        $rules["plates.36.lot"] = (in_array('test3', $selectedTests) && $testsRequired) ? 'required|string|max:255' : 'nullable|string';
        // Test C Doppio (5 plates, indices 32-35 and 37)
        for ($i = 32; $i < 36; $i++) {
            $rules["plates.{$i}"] = (in_array('test3', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        $rules["plates.37.id"] = (in_array('test3', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        $rules["plates.37.lot"] = (in_array('test3', $doubleTests) && $testsRequired) ? 'required|string|max:255' : 'nullable|string';

        $validatedData = $request->validate($rules);

        // Aggiungiamo l'ID dell'utente loggato ai dati da salvare
        $validatedData['user_id'] = Session::get('user')['id'];

        // Se il campione è non conforme, i test non vengono inviati.
        // Assicuriamoci che 'tests' e 'double_tests' siano array vuoti per evitare errori SQL.
        if ($validatedData['sample_conformity'] === 'non_conforme') {
            $validatedData['tests'] = [];
            $validatedData['double_tests'] = [];
        } else {
            // Se è conforme, assicuriamoci che la motivazione di non conformità sia nulla.
            $validatedData['non_conformity_reason'] = null;
        }

        // Creiamo una nuova istanza e la salviamo in due passaggi
        // per avere un controllo più esplicito, dato il bug riscontrato.
        $acceptance = new Acceptance();
        $acceptance->fill($validatedData);

        // Assicuriamoci esplicitamente che il valore sia corretto prima di salvare
        $acceptance->sample_conformity = $validatedData['sample_conformity'];
        $acceptance->save();

        // Reindirizziamo alla dashboard con un messaggio di successo
        return redirect()->route('acceptance.index', ['highlight' => $acceptance->id])->with('success', 'Accettazione campioni salvata con successo!');
    }

    /**
     * Mostra il form per modificare un'accettazione esistente.
     *
     * @param  \App\Models\Acceptance  $acceptance
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Acceptance $acceptance)
    {
        // Policy di autorizzazione: solo il creatore può modificare.
        $user = Session::get('user');
        $isAdmin = isset($user['user17025']) && $user['user17025'] == 1;
        $isLabManager = isset($user['user17025']) && $user['user17025'] == 4;
        $isOwner = $acceptance->user_id === $user['id'];

        // Eager load test results to check for signatures
        $acceptance->load('testAResult', 'testBResult', 'testCResult');

        $isAnyTestSigned = (
            ($acceptance->testAResult && $acceptance->testAResult->lab_signed_at) ||
            ($acceptance->testBResult && $acceptance->testBResult->lab_signed_at) ||
            ($acceptance->testCResult && $acceptance->testCResult->lab_signed_at)
        );

        return view('acceptance.edit', [
            'acceptance' => $acceptance,
            'currentUser' => $user, // Passa currentUser alla vista
            // Passa un flag alla vista per renderla di sola lettura se l'utente non è il proprietario
            // o se un test è già stato firmato.
            'is_readonly' => !$isOwner || $isAdmin || $isLabManager || $isAnyTestSigned
        ]);
    }

    /**
     * Aggiorna un'accettazione esistente nel database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Acceptance  $acceptance
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Acceptance $acceptance)
    {
        // Policy di autorizzazione: solo il creatore può modificare.
        $user = Session::get('user');
        $isAdmin = isset($user['user17025']) && $user['user17025'] == 1;
        $isLabManager = isset($user['user17025']) && $user['user17025'] == 4;
        $isOwner = $acceptance->user_id === $user['id'];

        if (!$isOwner || $isAdmin || $isLabManager) {
            abort(403, 'Azione non autorizzata.');
        }

        // Aggiungo il nuovo controllo: non si può modificare se un test è firmato.
        $acceptance->load('testAResult', 'testBResult', 'testCResult');
        $isAnyTestSigned = (
            ($acceptance->testAResult && $acceptance->testAResult->lab_signed_at) ||
            ($acceptance->testBResult && $acceptance->testBResult->lab_signed_at) ||
            ($acceptance->testCResult && $acceptance->testCResult->lab_signed_at)
        );

        if ($isAnyTestSigned) {
            abort(403, 'Azione non autorizzata: impossibile modificare l\'accettazione perché almeno un test associato è già stato firmato.');
        }

        $rules = [
            'acceptance_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('acceptances')->ignore($acceptance->id),
            ],
            'lotto' => 'required|string|max:255',
            'sampling_date' => 'required|date',
            'acceptance_date' => 'required|date',
            'tests' => 'required|array|min:1',
            'sample_conformity' => ['required', Rule::in(['conforme', 'non_conforme'])],
            'non_conformity_reason' => 'required_if:sample_conformity,non_conforme|nullable|string|max:2000',
            'tests.*' => 'string|in:test1,test2,test3',
            'double_tests' => 'nullable|array',
            'double_tests.*' => 'string|in:test1,test2,test3',
            'modification_reason' => 'required|string|min:10|max:500',
        ];

        $selectedTests = $request->input('tests', []);
        $doubleTests = $request->input('double_tests', []);

        // If sample is non-conforming, tests are not required
        $testsRequired = $request->input('sample_conformity') === 'conforme';
        if (!$testsRequired) {
            $rules['tests'] = 'nullable|array';
        }

        // Test A (2 plates, indices 0-1)
        for ($i = 0; $i < 1; $i++) { // Ora solo 1 piastra per il Test A standard
            $rules["plates.{$i}"] = (in_array('test1', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test A Doppio (2 plates, indices 2-3)
        for ($i = 2; $i < 3; $i++) { // Ora solo 1 piastra per il Test A in doppio
            $rules["plates.{$i}"] = (in_array('test1', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test B (12 plates, indices 4-15)
        for ($i = 4; $i < 16; $i++) {
            $rules["plates.{$i}"] = (in_array('test2', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test B Doppio (12 plates, indices 16-27)
        for ($i = 16; $i < 28; $i++) {
            $rules["plates.{$i}"] = (in_array('test2', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        // Test C (5 plates, indices 28-31 and 36)
        for ($i = 28; $i < 32; $i++) {
            $rules["plates.{$i}"] = (in_array('test3', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        $rules["plates.36.id"] = (in_array('test3', $selectedTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        $rules["plates.36.lot"] = (in_array('test3', $selectedTests) && $testsRequired) ? 'required|string|max:255' : 'nullable|string';
        // Test C Doppio (5 plates, indices 32-35 and 37)
        for ($i = 32; $i < 36; $i++) {
            $rules["plates.{$i}"] = (in_array('test3', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        }
        $rules["plates.37.id"] = (in_array('test3', $doubleTests) && $testsRequired) ? 'required|numeric' : 'nullable|numeric';
        $rules["plates.37.lot"] = (in_array('test3', $doubleTests) && $testsRequired) ? 'required|string|max:255' : 'nullable|string';

        $validatedData = $request->validate($rules);

        // Se il campione è non conforme, i test non vengono inviati.
        // Assicuriamoci che 'tests' e 'double_tests' siano array vuoti per aggiornare correttamente il record.
        if ($validatedData['sample_conformity'] === 'non_conforme') {
            $validatedData['tests'] = [];
            $validatedData['double_tests'] = [];
        } else {
            // Se è conforme, assicuriamoci che la motivazione di non conformità sia nulla.
            $validatedData['non_conformity_reason'] = null;
        }

        // Usiamo fill() e save() invece di update() per coerenza con la logica
        // di creazione e per un controllo più esplicito sul processo di salvataggio.
        $acceptance->fill($validatedData);
        // Assicuriamoci esplicitamente che il valore sia corretto prima di salvare
        $acceptance->sample_conformity = $validatedData['sample_conformity'];
        $acceptance->save();

        return redirect()->route('acceptance.index', ['highlight' => $acceptance->id])->with('success', 'Accettazione aggiornata con successo!');
    }

    /**
     * Genera il Rapporto di Prova in formato PDF.
     *
     * @param  \App\Models\Acceptance  $acceptance
     * @return \Illuminate\Http\Response
     */
    public function generatePdf(Acceptance $acceptance)
    {
        // Eager load relationships to avoid N+1 queries
        $acceptance->load('testAResult', 'testBResult', 'testCResult');

        // --- Fetch Method Revisions ---
        $methodRevisions = \App\Models\MethodRevision::all()->keyBy('method_key');

        // --- Inizio blocco recupero utenti via API (da refattorizzare in un service) ---
        $usersMap = [];
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
                $usersMap = collect($usersResponse->json('users'))->keyBy('id')->all();
            } else {
                Log::error("API call to get users failed in generatePdf with status " . $usersResponse->status());
            }
        } catch (\Exception $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API in generatePdf: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

        // Dati mancanti dal modello Acceptance, usiamo placeholder.
        // Questi dovrebbero essere aggiunti al modello Acceptance e ai relativi form.
        $productInfo = [
            'name' => 'XLD Agar', // Placeholder
            'code' => '10056', // Placeholder
            'expiry_date' => '2025-12-31', // Placeholder
        ];

        // Determina la data del report. Usa la data di validazione del Test C se disponibile, altrimenti la data odierna.
        $report_date = optional($acceptance->testCResult)->rl_signed_at
            ? $acceptance->testCResult->rl_signed_at->format('d.m.Y')
            : now()->format('d.m.Y');

        // Prepara i dati per la vista
        $data = [
            'acceptance' => $acceptance,
            'testAResult' => $acceptance->testAResult,
            'testBResult' => $acceptance->testBResult,
            'testCResult' => $acceptance->testCResult,
            'usersMap' => $usersMap,
            'productInfo' => $productInfo,
            'report_date' => $report_date,
            'methodRevisions' => $methodRevisions,
        ];

        // Aggiungo la variabile isPdfComplete ai dati passati alla vista
        $data['isPdfComplete'] = $this->isPdfComplete($acceptance);

        $pdf = Pdf::loadView('acceptance.pdf', $data);

        // Abilita l'esecuzione di script inline per il conteggio delle pagine
        $pdf->getDomPDF()->set_option("enable_php", true);

        // Imposta il nome del file
        $fileName = 'RDP_' . $acceptance->acceptance_number . '.pdf';

        // Mostra il PDF nel browser senza forzare il download
        return $pdf->stream($fileName);
    }

    /**
     * Checks if an Acceptance record is complete for PDF generation.
     * An acceptance is considered complete if all its required tests have been validated by the RL.
     *
     * @param Acceptance $acceptance
     * @return bool
     */
    private function isPdfComplete(Acceptance $acceptance): bool
    {
        $requiredTests = $acceptance->tests ?? [];

        // If no tests were selected for this acceptance, it cannot be "complete"
        if (empty($requiredTests)) {
            return false;
        }

        foreach ($requiredTests as $testKey) {
            if ($testKey === 'test1' && (!$acceptance->testAResult || !$acceptance->testAResult->rl_signature_id)) return false;
            if ($testKey === 'test2' && (!$acceptance->testBResult || !$acceptance->testBResult->rl_signature_id)) return false;
            if ($testKey === 'test3' && (!$acceptance->testCResult || !$acceptance->testCResult->rl_signed_at)) return false;
        }

        return true; // All required tests are present and validated
    }
}
