<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use App\Models\InstrumentItem;
use App\Models\TestBResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestSignedForValidation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http; // Re-add Http facade
use Illuminate\Support\Facades\Log;   // Re-add Log facade
use Illuminate\Http\Client\ConnectionException; // Re-add ConnectionException

class TestBController extends Controller
{
    /**
     * Mostra il form per inserire i risultati del Test B.
     */
    public function create(Acceptance $acceptance)
    {
        if (!in_array('test2', $acceptance->tests)) {
            return redirect()->route('acceptance.index')->with('error', 'Il Test B non è previsto per questa accettazione.');
        }

        $currentUser = Session::get('user');
        // Gli amministratori (ruolo 1) e i Responsabili Laboratorio (ruolo 4) non possono creare test.
        if (isset($currentUser['user17025']) && ($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4)) {
            return redirect()->route('acceptance.index')->with('error', 'Gli amministratori non possono creare nuovi test.');
        }

        // --- Inizio blocco recupero utenti via API ---
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
                Log::error("API call to get users failed in TestBController@create with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
            }
        } catch (ConnectionException $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API in TestBController@create (Connection Error): " . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Errore inatteso durante il recupero della lista utenti dall'API in TestBController@create: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

        $is_double_test_b = in_array('test2', $acceptance->double_tests ?? []);

        // Recupera gli ID delle piastre per il Test B
        $plate_ids = $acceptance->plates ?? [];

        // Quando si crea, pre-popoliamo i valori di default in base all'ordine di accettazione per 35C e 22C
        $selected_plates_run1 = [
            '35' => [
                'start_plate1' => $plate_ids[4] ?? null,
                'start_plate2' => $plate_ids[5] ?? null,
                'mid_plate1'   => $plate_ids[6] ?? null,
                'mid_plate2'   => $plate_ids[7] ?? null,
                'end_plate1'   => $plate_ids[8] ?? null,
                'end_plate2'   => $plate_ids[9] ?? null,
            ],
            '22' => [
                'start_plate1' => $plate_ids[10] ?? null,
                'start_plate2' => $plate_ids[11] ?? null,
                'mid_plate1'   => $plate_ids[12] ?? null,
                'mid_plate2'   => $plate_ids[13] ?? null,
                'end_plate1'   => $plate_ids[14] ?? null,
                'end_plate2'   => $plate_ids[15] ?? null,
            ],
        ];
        $selected_plates_run2 = [
            '35' => [
                'start_plate1' => $is_double_test_b ? ($plate_ids[16] ?? null) : null,
                'start_plate2' => $is_double_test_b ? ($plate_ids[17] ?? null) : null,
                'mid_plate1'   => $is_double_test_b ? ($plate_ids[18] ?? null) : null,
                'mid_plate2'   => $is_double_test_b ? ($plate_ids[19] ?? null) : null,
                'end_plate1'   => $is_double_test_b ? ($plate_ids[20] ?? null) : null,
                'end_plate2'   => $is_double_test_b ? ($plate_ids[21] ?? null) : null,
            ],
            '22' => [
                'start_plate1' => $is_double_test_b ? ($plate_ids[22] ?? null) : null,
                'start_plate2' => $is_double_test_b ? ($plate_ids[23] ?? null) : null,
                'mid_plate1'   => $is_double_test_b ? ($plate_ids[24] ?? null) : null,
                'mid_plate2'   => $is_double_test_b ? ($plate_ids[25] ?? null) : null,
                'end_plate1'   => $is_double_test_b ? ($plate_ids[26] ?? null) : null,
                'end_plate2'   => $is_double_test_b ? ($plate_ids[27] ?? null) : null,
            ],
        ];
        $test_b_plates = []; // Questa variabile non è più usata direttamente per la visualizzazione

        $incubators = InstrumentItem::whereHas('instrument', function ($query) {
            $query->where('name', 'Incubatore');
        })->get();

        return view('tests.test_b.create', [
            'acceptance' => $acceptance,
            'currentUser' => Session::get('user'),
            'test_b_plates' => $test_b_plates,
            'is_double_test_b' => $is_double_test_b,
            'selected_plates_run1' => $selected_plates_run1,
            'selected_plates_run2' => $selected_plates_run2,
            'incubators' => $incubators,
            'usersMap' => $usersMap,
            'is_initial_creation' => true, // Flag per la vista per inibire i campi del 2o step
        ]);
    }

    /**
     * Salva i risultati del Test B.
     */
    public function store(Request $request, Acceptance $acceptance)
    {
        if (!in_array('test2', $acceptance->tests) || $acceptance->testBResult()->exists()) {
            abort(403, 'Azione non permessa.');
        }

        $validatedData = $this->validateRequest($request, false);

        $dataToSave = $this->prepareData($validatedData, $request);
        $dataToSave['acceptance_id'] = $acceptance->id;
        $dataToSave['operator_id'] = Session::get('user')['id'];

        // Poiché gli ID delle piastre non vengono più inviati dal form, li recuperiamo direttamente dall'accettazione.
        $is_double_test_b = in_array('test2', $acceptance->double_tests ?? []);
        $plate_ids = $acceptance->plates ?? [];

        // Mappa gli ID delle piastre ai campi corretti del database per il Run 1
        $dataToSave['plate_id_start_plate1_35_run1'] = $plate_ids[4] ?? null;
        $dataToSave['plate_id_start_plate2_35_run1'] = $plate_ids[5] ?? null;
        $dataToSave['plate_id_mid_plate1_35_run1']   = $plate_ids[6] ?? null;
        $dataToSave['plate_id_mid_plate2_35_run1']   = $plate_ids[7] ?? null;
        $dataToSave['plate_id_end_plate1_35_run1']   = $plate_ids[8] ?? null;
        $dataToSave['plate_id_end_plate2_35_run1']   = $plate_ids[9] ?? null;
        $dataToSave['plate_id_start_plate1_22_run1'] = $plate_ids[10] ?? null;
        $dataToSave['plate_id_start_plate2_22_run1'] = $plate_ids[11] ?? null;
        $dataToSave['plate_id_mid_plate1_22_run1']   = $plate_ids[12] ?? null;
        $dataToSave['plate_id_mid_plate2_22_run1']   = $plate_ids[13] ?? null;
        $dataToSave['plate_id_end_plate1_22_run1']   = $plate_ids[14] ?? null;
        $dataToSave['plate_id_end_plate2_22_run1']   = $plate_ids[15] ?? null;

        if ($is_double_test_b) {
            // Mappa gli ID delle piastre ai campi corretti del database per il Run 2
            $dataToSave['plate_id_start_plate1_35_run2'] = $plate_ids[16] ?? null;
            $dataToSave['plate_id_start_plate2_35_run2'] = $plate_ids[17] ?? null;
            $dataToSave['plate_id_mid_plate1_35_run2']   = $plate_ids[18] ?? null;
            $dataToSave['plate_id_mid_plate2_35_run2']   = $plate_ids[19] ?? null;
            $dataToSave['plate_id_end_plate1_35_run2']   = $plate_ids[20] ?? null;
            $dataToSave['plate_id_end_plate2_35_run2']   = $plate_ids[21] ?? null;
            $dataToSave['plate_id_start_plate1_22_run2'] = $plate_ids[22] ?? null;
            $dataToSave['plate_id_start_plate2_22_run2'] = $plate_ids[23] ?? null;
            $dataToSave['plate_id_mid_plate1_22_run2']   = $plate_ids[24] ?? null;
            $dataToSave['plate_id_mid_plate2_22_run2']   = $plate_ids[25] ?? null;
            $dataToSave['plate_id_end_plate1_22_run2']   = $plate_ids[26] ?? null;
            $dataToSave['plate_id_end_plate2_22_run2']   = $plate_ids[27] ?? null;
        }

        // Assicura che tutti i campi fillable non forniti nella creazione iniziale siano impostati a null.
        // Questo previene errori SQL se le colonne del DB sono NULLABLE ma non hanno un valore di default.
        // Se le colonne NON sono NULLABLE, questo causerà un errore "cannot be null", indicando
        // la necessità di una modifica allo schema del database.
        $model = new TestBResult();
        $allFillable = $model->getFillable();
        foreach ($allFillable as $fillableKey) {
            if (!array_key_exists($fillableKey, $dataToSave)) {
                $dataToSave[$fillableKey] = null;
            }
        }

        TestBResult::create($dataToSave);

        // --- Generazione URL Google Calendar ---
        $reminderDays = env('TEST_B_INCUBATION_REMINDER_DAYS', 7);
        $startDate = \Carbon\Carbon::parse($dataToSave['test_start_datetime']);
        $reminderDate = $startDate->copy()->addDays($reminderDays);

        $eventStartDate = $reminderDate->format('Ymd');
        $eventEndDate = $reminderDate->copy()->addDay()->format('Ymd'); // All-day event, so end date is the next day

        $title = urlencode("Completamento Test B - Lotto: {$acceptance->lotto}");
        $details = urlencode(
            "Promemoria per completare il Test B (Produttività) per l'accettazione N. {$acceptance->acceptance_number}.\n\n" .
            "Link all'applicazione: " . route('acceptance.index')
        );

        $calendarUrl = "https://www.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$eventStartDate}/{$eventEndDate}&details={$details}";

        return redirect()->route('acceptance.index')
            ->with('success', 'Risultati iniziali del Test B salvati con successo!')
            ->with('calendarUrl', $calendarUrl);
    }

    /**
     * Mostra il form per modificare i risultati del Test B.
     */
    public function edit(TestBResult $test_b_result)
    {
        $currentUser = Session::get('user');
        $isOwner = $test_b_result->operator_id === $currentUser['id'];
        $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;
        $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
        $is_completion_phase = is_null($test_b_result->test_end_datetime);
        $is_readonly = $isAdmin || $isLabManager || !$isOwner || $test_b_result->lab_signed_at || $test_b_result->rl_signature_id;        

        // --- Inizio blocco recupero utenti via API (re-added) ---
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

            // Includi l'username dell'utente corrente nella richiesta API per 'get_users'
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
                Log::error("API call to get users failed in TestBController@edit with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
            }
        } catch (ConnectionException $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API in TestBController@edit (Connection Error): " . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Errore inatteso durante il recupero della lista utenti dall'API in TestBController@edit: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

        $acceptance = $test_b_result->acceptance;

        $is_double_test_b = in_array('test2', $acceptance->double_tests ?? []);

        // Recupera gli ID delle piastre per il Test B
        $plate_ids = $acceptance->plates ?? [];
        // Populate selected plates for the dropdowns when editing
        $selected_plates_run1 = [ // Nested by temperature
            '35' => [
                'start_plate1' => $test_b_result->plate_id_start_plate1_35_run1,
                'start_plate2' => $test_b_result->plate_id_start_plate2_35_run1,
                'mid_plate1'   => $test_b_result->plate_id_mid_plate1_35_run1,
                'mid_plate2'   => $test_b_result->plate_id_mid_plate2_35_run1,
                'end_plate1'   => $test_b_result->plate_id_end_plate1_35_run1,
                'end_plate2'   => $test_b_result->plate_id_end_plate2_35_run1,
            ],
            '22' => [
                'start_plate1' => $test_b_result->plate_id_start_plate1_22_run1,
                'start_plate2' => $test_b_result->plate_id_start_plate2_22_run1,
                'mid_plate1'   => $test_b_result->plate_id_mid_plate1_22_run1,
                'mid_plate2'   => $test_b_result->plate_id_mid_plate2_22_run1,
                'end_plate1'   => $test_b_result->plate_id_end_plate1_22_run1,
                'end_plate2'   => $test_b_result->plate_id_end_plate2_22_run1,
            ],
        ];
        $selected_plates_run2 = [ // Nested by temperature
            '35' => [
                'start_plate1' => $test_b_result->plate_id_start_plate1_35_run2,
                'start_plate2' => $test_b_result->plate_id_start_plate2_35_run2,
                'mid_plate1'   => $test_b_result->plate_id_mid_plate1_35_run2,
                'mid_plate2'   => $test_b_result->plate_id_mid_plate2_35_run2,
                'end_plate1'   => $test_b_result->plate_id_end_plate1_35_run2,
                'end_plate2'   => $test_b_result->plate_id_end_plate2_35_run2,
            ],
            '22' => [
                'start_plate1' => $test_b_result->plate_id_start_plate1_22_run2,
                'start_plate2' => $test_b_result->plate_id_start_plate2_22_run2,
                'mid_plate1'   => $test_b_result->plate_id_mid_plate1_22_run2,
                'mid_plate2'   => $test_b_result->plate_id_mid_plate2_22_run2,
                'end_plate1'   => $test_b_result->plate_id_end_plate1_22_run2,
                'end_plate2'   => $test_b_result->plate_id_end_plate2_22_run2,
            ],
        ];
        $test_b_plates = []; // This variable is no longer directly used for display in the same way

        $incubators = InstrumentItem::whereHas('instrument', function ($query) {
            $query->where('name', 'Incubatore');
        })->get();

        return view('tests.test_b.create', [
            'acceptance' => $acceptance,
            'test_b_result' => $test_b_result,
            'currentUser' => $currentUser,
            'is_readonly' => $is_readonly,
            'test_b_plates' => $test_b_plates,
            'is_double_test_b' => $is_double_test_b,
            'selected_plates_run1' => $selected_plates_run1,
            'selected_plates_run2' => $selected_plates_run2,
            'usersMap' => $usersMap, // Pass usersMap to the view
            'incubators' => $incubators,
            'is_completion_phase' => $is_completion_phase,
        ]);
    }

    /**
     * Aggiorna i risultati del Test B.
     */
    public function update(Request $request, TestBResult $test_b_result)
    {
        $currentUser = Session::get('user');
        $isOwner = $test_b_result->operator_id === $currentUser['id'];
        $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;
        $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
        if (!$isOwner || $test_b_result->lab_signed_at || $test_b_result->rl_signature_id || $isAdmin || $isLabManager) {
            abort(403, 'Azione non autorizzata: il test è firmato o validato e non può essere modificato.');
        }

        $validatedData = $this->validateRequest($request, true);
        $dataToSave = $this->prepareData($validatedData, $request);
        
        if (isset($validatedData['modification_reason'])) {
            $dataToSave['modification_reason'] = $validatedData['modification_reason'];
        }

        $test_b_result->update($dataToSave);

        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test B aggiornati con successo!');
    }

    /**
     * Appone la firma del tecnico di laboratorio al test.
     */
    public function sign(Request $request, TestBResult $test_b_result)
    {
        $currentUser = Session::get('user');
        
        // Policy 1: Solo i tecnici di laboratorio (ruolo 3) possono firmare.
        $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;
        if (!$isLabTechnician) {
            abort(403, 'Azione non autorizzata: solo i tecnici di laboratorio possono firmare i test.');
        }

        $isOwner = $test_b_result->operator_id === $currentUser['id'];

        // Policy 2: Solo il proprietario del test può firmare.
        if (!$isOwner) {
            abort(403, 'Azione non autorizzata: solo l\'operatore che ha compilato il test può firmare.');
        }
        // Policy 3: Il test non deve essere già firmato o validato.
        if ($test_b_result->lab_signed_at) {
            return redirect()->route('acceptance.index')->with('error', 'Il test è già stato firmato.');
        }
        if ($test_b_result->rl_signature_id) {
            abort(403, 'Azione non autorizzata: il test è già stato validato e non può essere firmato.');
        }

        // Aggiorna il record con i dati della firma
        $test_b_result->lab_signature_id = $currentUser['id'];
        $test_b_result->lab_signed_at = now();
        $test_b_result->save();

        // --- Invia notifica email ai RL ---
        $rlEmails = $this->getRlEmails();
        if (empty($rlEmails)) {
            Session::flash('notification_warning', 'ATTENZIONE: Nessun Responsabile di Laboratorio con email valida trovato. La notifica non è stata inviata.');
        } else {
            $operatorName = $currentUser['operatore'] ?? 'N/D';
            $testType = 'Test B - Produttività';
            $acceptance = $test_b_result->acceptance;
            try {
                Mail::to($rlEmails)->send(new TestSignedForValidation($test_b_result, $acceptance, $operatorName, $testType));
                Session::flash('notification_success', 'Notifica di validazione inviata con successo a: ' . implode(', ', $rlEmails));
            } catch (\Exception $e) {
                Log::error("Invio email di notifica fallito per Test B ID {$test_b_result->id}: " . $e->getMessage());
                Session::flash('notification_error', 'ATTENZIONE: La notifica di validazione non è stata inviata. Controllare la configurazione del server di posta. Errore: ' . $e->getMessage());
            }
        }
        // --- Fine notifica ---

        return redirect()->route('acceptance.index')->with('success', 'Test B firmato con successo!');
    }

    /**
     * Valida il test da parte del Responsabile Laboratorio (RL).
     */
    public function validateTest(Request $request, TestBResult $test_b_result)
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

        // Policy 2: Il test deve essere stato firmato dal tecnico.
        if (!$test_b_result->lab_signed_at) {
            return redirect()->route('test-b.edit', $test_b_result->id)->with('error', 'Il test non può essere validato perché non è stato ancora firmato dal tecnico.'); // Reindirizza alla pagina di modifica
        }

        // Policy 3: Il test non deve essere già stato validato.
        if ($test_b_result->rl_signature_id) {
            return redirect()->route('test-b.edit', $test_b_result->id)->with('error', 'Il test è già stato validato.'); // Reindirizza alla pagina di modifica
        }

        // Aggiorna il record con i dati della validazione
        $test_b_result->rl_signature_id = $currentUser['id'];
        $test_b_result->rl_signed_at = now();
        $test_b_result->save();
        return redirect()->route('test-b.edit', $test_b_result->id)->with('success', 'Test B validato con successo dal Responsabile Laboratorio!'); // Reindirizza alla pagina di modifica
    }

    /**
     * Valida la richiesta in ingresso.
     */
    private function validateRequest(Request $request, bool $isUpdate = false): array
    {
        // Determina il contesto: accettazione e se è un test doppio
        if ($isUpdate) {
            $test_b_result = $request->route('test_b_result');
            $acceptance = $test_b_result->acceptance;
        } else {
            $acceptance = $request->route('acceptance');
        }
        $is_double_test_b = $acceptance ? in_array('test2', $acceptance->double_tests ?? []) : false;

        // --- Definizione Gruppi di Regole ---
        $growthRule = ['required', Rule::in(['rilevata', 'non_rilevata'])];

        // Regole per la prima fase di compilazione (Creazione)
        $initial_rules = [
            'test_start_date' => 'required|date',
            'test_start_time' => 'required|date_format:H:i',
            'incubator_35_run1' => 'required|string|max:255',
            'incubation_start_date_35_run1' => 'required|date',
            'incubation_start_time_35_run1' => 'required|date_format:H:i',
            'temperature_35_run1' => 'required|numeric|min:0|max:50',
            'incubator_22_run1' => 'required|string|max:255',
            'incubation_start_date_22_run1' => 'required|date',
            'incubation_start_time_22_run1' => 'required|date_format:H:i',
            'temperature_22_run1' => 'required|numeric|min:0|max:50',
        ];

        // Regole per la seconda fase di compilazione (Completamento)
        $completion_rules = [
            'test_end_date' => 'required|date|after_or_equal:test_start_date',
            'test_end_time' => 'required|date_format:H:i',
            'incubation_end_date_35_run1' => 'required|date|after_or_equal:incubation_start_date_35_run1',
            'incubation_end_time_35_run1' => 'required|date_format:H:i',
            'growth_result_35_start_plate1_run1' => $growthRule,
            'growth_result_35_start_plate2_run1' => $growthRule,
            'growth_result_35_mid_plate1_run1' => $growthRule,
            'growth_result_35_mid_plate2_run1' => $growthRule,
            'growth_result_35_end_plate1_run1' => $growthRule,
            'growth_result_35_end_plate2_run1' => $growthRule,
            'incubation_end_date_22_run1' => 'required|date|after_or_equal:incubation_start_date_22_run1',
            'incubation_end_time_22_run1' => 'required|date_format:H:i',
            'growth_result_22_start_plate1_run1' => $growthRule,
            'growth_result_22_start_plate2_run1' => $growthRule,
            'growth_result_22_mid_plate1_run1' => $growthRule,
            'growth_result_22_mid_plate2_run1' => $growthRule,
            'growth_result_22_end_plate1_run1' => $growthRule,
            'growth_result_22_end_plate2_run1' => $growthRule,
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];

        // Aggiungi regole per il doppio test se necessario
        if ($is_double_test_b) {
            $initial_rules_run2 = [
                'incubator_35_run2' => 'required|string|max:255',
                'incubation_start_date_35_run2' => 'required|date',
                'incubation_start_time_35_run2' => 'required|date_format:H:i',
                'temperature_35_run2' => 'required|numeric|min:0|max:50',
                'incubator_22_run2' => 'required|string|max:255',
                'incubation_start_date_22_run2' => 'required|date',
                'incubation_start_time_22_run2' => 'required|date_format:H:i',
                'temperature_22_run2' => 'required|numeric|min:0|max:50',
            ];
            $initial_rules = array_merge($initial_rules, $initial_rules_run2);

            $completion_rules_run2 = [
                'incubation_end_date_35_run2' => 'required|date|after_or_equal:incubation_start_date_35_run2',
                'incubation_end_time_35_run2' => 'required|date_format:H:i',
                'growth_result_35_start_plate1_run2' => $growthRule,
                'growth_result_35_start_plate2_run2' => $growthRule,
                'growth_result_35_mid_plate1_run2' => $growthRule,
                'growth_result_35_mid_plate2_run2' => $growthRule,
                'growth_result_35_end_plate1_run2' => $growthRule,
                'growth_result_35_end_plate2_run2' => $growthRule,
                'incubation_end_date_22_run2' => 'required|date|after_or_equal:incubation_start_date_22_run2',
                'incubation_end_time_22_run2' => 'required|date_format:H:i',
                'growth_result_22_start_plate1_run2' => $growthRule,
                'growth_result_22_start_plate2_run2' => $growthRule,
                'growth_result_22_mid_plate1_run2' => $growthRule,
                'growth_result_22_mid_plate2_run2' => $growthRule,
                'growth_result_22_end_plate1_run2' => $growthRule,
                'growth_result_22_end_plate2_run2' => $growthRule,
            ];
            $completion_rules = array_merge($completion_rules, $completion_rules_run2);
        }

        $modification_reason_rule = ['modification_reason' => 'required|string|min:10|max:500'];

        // --- Logica di Validazione Dinamica ---
        $rules = [];
        if (!$isUpdate) {
            // --- CASO 1: Creazione iniziale ---
            $rules = $initial_rules;
        } else {
            $test_b_result = $request->route('test_b_result');
            $is_already_complete = !is_null($test_b_result->test_end_datetime);
            $is_completing_now = $request->filled('test_end_date') && !empty($request->input('test_end_date'));

            if ($is_already_complete) {
                // --- CASO 2: Modifica di un test già completo ---
                // Tutti i campi sono obbligatori + motivazione
                $rules = array_merge($initial_rules, $completion_rules, $modification_reason_rule);
            } else {
                // Il test non è ancora completo
                if ($is_completing_now) {
                    // --- CASO 3: Completamento del test ---
                    // Tutti i campi sono obbligatori, ma senza motivazione
                    $rules = array_merge($initial_rules, $completion_rules);
                } else {
                    // --- CASO 4: Modifica dei dati iniziali (prima del completamento) ---
                    // Solo i campi iniziali sono obbligatori + motivazione
                    $rules = array_merge($initial_rules, $modification_reason_rule);
                }
            }
        }

        // Messaggi di errore personalizzati in italiano
        $messages = [
            'required' => 'Il campo :attribute è obbligatorio.',
            'string' => 'Il campo :attribute deve essere una stringa.',
            'max' => 'Il campo :attribute non può superare i :max caratteri.',
            'date' => 'Il campo :attribute deve essere una data valida.',
            'date_format' => 'Il campo :attribute deve essere nel formato HH:MM.',
            'after_or_equal' => 'Il campo :attribute deve essere una data successiva o uguale a :date.',
            'numeric' => 'Il campo :attribute deve essere un numero.',
            'min' => 'Il campo :attribute deve essere almeno :min.',
            'in' => 'Il valore selezionato per :attribute non è valido.',
            'required_if' => 'Il campo :attribute è obbligatorio quando :other è :value.',
            'modification_reason.min' => 'La motivazione della modifica deve contenere almeno :min caratteri.',

            // Messaggi specifici per i campi
            'test_start_date.required' => 'La data di inizio prova è obbligatoria.',
            'test_start_time.required' => 'L\'ora di inizio prova è obbligatoria.',
            'test_end_date.required' => 'La data di fine prova è obbligatoria.',
            'test_end_date.after_or_equal' => 'La data di fine prova deve essere successiva o uguale alla data di inizio prova.',
            'test_end_time.required' => 'L\'ora di fine prova è obbligatoria.',

            'incubator_35_run1.required' => 'L\'incubatore (35°C, Run 1) è obbligatorio.',
            'incubation_start_date_35_run1.required' => 'La data di inizio incubazione (35°C, Run 1) è obbligatoria.',
            'incubation_start_time_35_run1.required' => 'L\'ora di inizio incubazione (35°C, Run 1) è obbligatoria.',
            'incubation_end_date_35_run1.required' => 'La data di fine incubazione (35°C, Run 1) è obbligatoria.',
            'incubation_end_date_35_run1.after_or_equal' => 'La data di fine incubazione (35°C, Run 1) deve essere successiva o uguale alla data di inizio.',
            'incubation_end_time_35_run1.required' => 'L\'ora di fine incubazione (35°C, Run 1) è obbligatoria.',
            'temperature_35_run1.required' => 'La temperatura (35°C, Run 1) è obbligatoria.',
            'growth_result_35_start_plate1_run1.required' => 'Il risultato di crescita (Inizio, P1, 35°C, Run 1) è obbligatorio.',
            'growth_result_35_start_plate2_run1.required' => 'Il risultato di crescita (Inizio, P2, 35°C, Run 1) è obbligatorio.',
            'growth_result_35_mid_plate1_run1.required' => 'Il risultato di crescita (Metà, P1, 35°C, Run 1) è obbligatorio.',
            'growth_result_35_mid_plate2_run1.required' => 'Il risultato di crescita (Metà, P2, 35°C, Run 1) è obbligatorio.',
            'growth_result_35_end_plate1_run1.required' => 'Il risultato di crescita (Fine, P1, 35°C, Run 1) è obbligatorio.',
            'growth_result_35_end_plate2_run1.required' => 'Il risultato di crescita (Fine, P2, 35°C, Run 1) è obbligatorio.',

            'incubator_22_run1.required' => 'L\'incubatore (22°C, Run 1) è obbligatorio.',
            'incubation_start_date_22_run1.required' => 'La data di inizio incubazione (22°C, Run 1) è obbligatoria.',
            'incubation_start_time_22_run1.required' => 'L\'ora di inizio incubazione (22°C, Run 1) è obbligatoria.',
            'incubation_end_date_22_run1.required' => 'La data di fine incubazione (22°C, Run 1) è obbligatoria.',
            'incubation_end_date_22_run1.after_or_equal' => 'La data di fine incubazione (22°C, Run 1) deve essere successiva o uguale alla data di inizio.',
            'incubation_end_time_22_run1.required' => 'L\'ora di fine incubazione (22°C, Run 1) è obbligatoria.',
            'temperature_22_run1.required' => 'La temperatura (22°C, Run 1) è obbligatoria.',
            'growth_result_22_start_plate1_run1.required' => 'Il risultato di crescita (Inizio, P1, 22°C, Run 1) è obbligatorio.',
            'growth_result_22_start_plate2_run1.required' => 'Il risultato di crescita (Inizio, P2, 22°C, Run 1) è obbligatorio.',
            'growth_result_22_mid_plate1_run1.required' => 'Il risultato di crescita (Metà, P1, 22°C, Run 1) è obbligatorio.',
            'growth_result_22_mid_plate2_run1.required' => 'Il risultato di crescita (Metà, P2, 22°C, Run 1) è obbligatorio.',
            'growth_result_22_end_plate1_run1.required' => 'Il risultato di crescita (Fine, P1, 22°C, Run 1) è obbligatorio.',
            'growth_result_22_end_plate2_run1.required' => 'Il risultato di crescita (Fine, P2, 22°C, Run 1) è obbligatorio.',

            'outcome.required' => 'L\'esito del test è obbligatorio.',
            'non_compliance_ref.required_if' => 'Il riferimento di non conformità è obbligatorio quando l\'esito è "Non Idoneo".',

            // Messaggi per il Run 2 (se doppio test)
            'incubator_35_run2.required' => 'L\'incubatore (35°C, Run 2) è obbligatorio.',
            'incubation_start_date_35_run2.required' => 'La data di inizio incubazione (35°C, Run 2) è obbligatoria.',
            'incubation_start_time_35_run2.required' => 'L\'ora di inizio incubazione (35°C, Run 2) è obbligatoria.',
            'incubation_end_date_35_run2.required' => 'La data di fine incubazione (35°C, Run 2) è obbligatoria.',
            'incubation_end_date_35_run2.after_or_equal' => 'La data di fine incubazione (35°C, Run 2) deve essere successiva o uguale alla data di inizio.',
            'incubation_end_time_35_run2.required' => 'L\'ora di fine incubazione (35°C, Run 2) è obbligatoria.',
            'temperature_35_run2.required' => 'La temperatura (35°C, Run 2) è obbligatoria.',
            'growth_result_35_start_plate1_run2.required' => 'Il risultato di crescita (Inizio, P1, 35°C, Run 2) è obbligatorio.',
            'growth_result_35_start_plate2_run2.required' => 'Il risultato di crescita (Inizio, P2, 35°C, Run 2) è obbligatorio.',
            'growth_result_35_mid_plate1_run2.required' => 'Il risultato di crescita (Metà, P1, 35°C, Run 2) è obbligatorio.',
            'growth_result_35_mid_plate2_run2.required' => 'Il risultato di crescita (Metà, P2, 35°C, Run 2) è obbligatorio.',
            'growth_result_35_end_plate1_run2.required' => 'Il risultato di crescita (Fine, P1, 35°C, Run 2) è obbligatorio.',
            'growth_result_35_end_plate2_run2.required' => 'Il risultato di crescita (Fine, P2, 35°C, Run 2) è obbligatorio.',

            'incubator_22_run2.required' => 'L\'incubatore (22°C, Run 2) è obbligatorio.',
            'incubation_start_date_22_run2.required' => 'La data di inizio incubazione (22°C, Run 2) è obbligatoria.',
            'incubation_start_time_22_run2.required' => 'L\'ora di inizio incubazione (22°C, Run 2) è obbligatorio.',
            'incubation_end_date_22_run2.required' => 'La data di fine incubazione (22°C, Run 2) è obbligatoria.',
            'incubation_end_date_22_run2.after_or_equal' => 'La data di fine incubazione (22°C, Run 2) deve essere successiva o uguale alla data di inizio.',
            'incubation_end_time_22_run2.required' => 'L\'ora di fine incubazione (22°C, Run 2) è obbligatoria.',
            'temperature_22_run2.required' => 'La temperatura (22°C, Run 2) è obbligatoria.',
            'growth_result_22_start_plate1_run2.required' => 'Il risultato di crescita (Inizio, P1, 22°C, Run 2) è obbligatorio.',
            'growth_result_22_start_plate2_run2.required' => 'Il risultato di crescita (Inizio, P2, 22°C, Run 2) è obbligatorio.',
            'growth_result_22_mid_plate1_run2.required' => 'Il risultato di crescita (Metà, P1, 22°C, Run 2) è obbligatorio.',
            'growth_result_22_mid_plate2_run2.required' => 'Il risultato di crescita (Metà, P2, 22°C, Run 2) è obbligatorio.',
            'growth_result_22_end_plate1_run2.required' => 'Il risultato di crescita (Fine, P1, 22°C, Run 2) è obbligatorio.',
            'growth_result_22_end_plate2_run2.required' => 'Il risultato di crescita (Fine, P2, 22°C, Run 2) è obbligatorio.',

            'modification_reason.required' => 'La motivazione della modifica è obbligatoria.',
            'modification_reason.min' => 'La motivazione della modifica deve contenere almeno :min caratteri.',
        ];

        return $request->validate($rules, $messages);
    }

    /**
     * Prepara i dati combinando date e ore.
     */
    private function prepareData(array $validatedData, Request $request): array
    {
        $data = $validatedData;

        $acceptance = $request->route('acceptance'); // Dalla rotta 'store'
        if (!$acceptance) {
            $test_b_result = $request->route('test_b_result'); // Dalla rotta 'update'
            if ($test_b_result) {
                $acceptance = $test_b_result->acceptance;
            }
        }

        $is_double_test_b = $acceptance ? in_array('test2', $acceptance->double_tests ?? []) : false;

        // Rimuove i campi separati di data/ora
        unset($data['test_start_date'], $data['test_start_time'], $data['test_end_date'], $data['test_end_time']);
        unset($data['incubation_start_date_35_run1'], $data['incubation_start_time_35_run1'], $data['incubation_end_date_35_run1'], $data['incubation_end_time_35_run1']);
        unset($data['incubation_start_date_22_run1'], $data['incubation_start_time_22_run1'], $data['incubation_end_date_22_run1'], $data['incubation_end_time_22_run1']);
        if ($is_double_test_b) {
            unset($data['incubation_start_date_35_run2'], $data['incubation_start_time_35_run2'], $data['incubation_end_date_35_run2'], $data['incubation_end_time_35_run2']);
            unset($data['incubation_start_date_22_run2'], $data['incubation_start_time_22_run2'], $data['incubation_end_date_22_run2'], $data['incubation_end_time_22_run2']);
        }

        // Combina in campi datetime
        $data['test_start_datetime'] = $request->test_start_date . ' ' . $request->test_start_time;

        if ($request->filled('test_end_date') && $request->filled('test_end_time')) {
            $data['test_end_datetime'] = $request->test_end_date . ' ' . $request->test_end_time;
        } else {
            $data['test_end_datetime'] = null;
        }
        
        // Handle run 1 incubation datetimes
        if ($request->filled('incubation_start_date_35_run1') && $request->filled('incubation_start_time_35_run1')) {
            $data['incubation_start_datetime_35_run1'] = $request->incubation_start_date_35_run1 . ' ' . $request->incubation_start_time_35_run1;
        }
        if ($request->filled('incubation_end_date_35_run1') && $request->filled('incubation_end_time_35_run1')) {
            $data['incubation_end_datetime_35_run1'] = $request->incubation_end_date_35_run1 . ' ' . $request->incubation_end_time_35_run1;
        } else {
            $data['incubation_end_datetime_35_run1'] = null;
        }
        if ($request->filled('incubation_start_date_22_run1') && $request->filled('incubation_start_time_22_run1')) {
            $data['incubation_start_datetime_22_run1'] = $request->incubation_start_date_22_run1 . ' ' . $request->incubation_start_time_22_run1;
        }
        if ($request->filled('incubation_end_date_22_run1') && $request->filled('incubation_end_time_22_run1')) {
            $data['incubation_end_datetime_22_run1'] = $request->incubation_end_date_22_run1 . ' ' . $request->incubation_end_time_22_run1;
        } else {
            $data['incubation_end_datetime_22_run1'] = null;
        }

        // Handle run 2 incubation datetimes if it's a double test
        if ($is_double_test_b) {
            if ($request->filled('incubation_start_date_35_run2') && $request->filled('incubation_start_time_35_run2')) {
                $data['incubation_start_datetime_35_run2'] = $request->incubation_start_date_35_run2 . ' ' . $request->incubation_start_time_35_run2;
            }
            if ($request->filled('incubation_end_date_35_run2') && $request->filled('incubation_end_time_35_run2')) {
                $data['incubation_end_datetime_35_run2'] = $request->incubation_end_date_35_run2 . ' ' . $request->incubation_end_time_35_run2;
            } else {
                $data['incubation_end_datetime_35_run2'] = null;
            }
            if ($request->filled('incubation_start_date_22_run2') && $request->filled('incubation_start_time_22_run2')) {
                $data['incubation_start_datetime_22_run2'] = $request->incubation_start_date_22_run2 . ' ' . $request->incubation_start_time_22_run2;
            }
            if ($request->filled('incubation_end_date_22_run2') && $request->filled('incubation_end_time_22_run2')) {
                $data['incubation_end_datetime_22_run2'] = $request->incubation_end_date_22_run2 . ' ' . $request->incubation_end_time_22_run2;
            } else {
                $data['incubation_end_datetime_22_run2'] = null;
            }
        }

        return $data;
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