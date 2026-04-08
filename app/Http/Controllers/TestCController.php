<?php

namespace App\Http\Controllers;

use App\Models\InstrumentItem;
use App\Models\MethodRevision;
use App\Models\Acceptance;
use App\Models\TestCResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestSignedForValidation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class TestCController extends Controller
{
    /**
     * Mostra il form per inserire i risultati del Test C.
     */
    public function create(Acceptance $acceptance)
    {
        if (!in_array('test3', $acceptance->tests)) {
            return redirect()->route('acceptance.index')->with('error', 'Il Test C non è previsto per questa accettazione.');
        }

        $currentUser = Session::get('user');
        // Admin (1), RL (4) e QA (5) non possono creare test.
        if (isset($currentUser['user17025']) && in_array($currentUser['user17025'], [1, 4, 5])) {
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
                Log::error("API call to get users failed in TestCController@create with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
            }
        } catch (ConnectionException $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API in TestCController@create (Connection Error): " . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Errore inatteso durante il recupero della lista utenti dall'API in TestCController@create: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

        $is_double_test_c = in_array('test3', $acceptance->double_tests ?? []);

        // Global test start/end datetimes are not per run, so they are not passed with suffix.
        // They are handled directly in the view for initial values.        

        // Recupera gli ID e i lotti delle piastre per il Test C
        // Assumiamo che $acceptance->plates sia un array di array associativi:
        // [index => ['id' => 'plate_id', 'lot' => 'plate_lot']]
        // Aggiunta logica di retrocompatibilità per accettazioni con il vecchio formato di 'plates'
        $acceptance_plates_data = $acceptance->plates ?? [];

        $get_plate_info = function($index) use ($acceptance_plates_data) {
            $data = $acceptance_plates_data[$index] ?? null;
            // If it's an array, assume it has 'id' and 'lot' (for TSA plates)
            if (is_array($data)) return ['id' => $data['id'] ?? null, 'lot' => $data['lot'] ?? null];
            // Otherwise, it's just the ID (for non-TSA plates or old format)
            return ['id' => $data, 'lot' => null];
        };

        $selected_plates = [
            'start_lotto'   => $get_plate_info(28),
            'mid_lotto'     => $get_plate_info(29),
            'end_lotto'     => $get_plate_info(30),
            'control_blank' => $get_plate_info(31),
            'tsa_sheep_blood' => $get_plate_info(36),
        ];

        $selected_plates_run2 = [
            'start_lotto'   => $is_double_test_c ? $get_plate_info(32) : ['id' => null, 'lot' => null],
            'mid_lotto'     => $is_double_test_c ? $get_plate_info(33) : ['id' => null, 'lot' => null],
            'end_lotto'     => $is_double_test_c ? $get_plate_info(34) : ['id' => null, 'lot' => null],
            'control_blank' => $is_double_test_c ? $get_plate_info(35) : ['id' => null, 'lot' => null],
            'tsa_sheep_blood' => $is_double_test_c ? $get_plate_info(37) : ['id' => null, 'lot' => null],
        ];

        $incubators = InstrumentItem::whereHas('instrument', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['incubatore']);
        })->get();


        $pipettes = InstrumentItem::whereHas('instrument', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['pipetta']);
        })->get();

        $methodRevisions = MethodRevision::all()->keyBy('method_key');

        return view('tests.test_c.create', [
            'acceptance' => $acceptance,
            'currentUser' => Session::get('user'),
            'is_double_test_c' => $is_double_test_c,
            'selected_plates' => $selected_plates,
            'selected_plates_run2' => $selected_plates_run2,
            'incubators' => $incubators,
            'pipettes' => $pipettes,
            'usersMap' => $usersMap,
            'methodRevisions' => $methodRevisions,
            'is_initial_creation' => true, // Flag per la vista per inibire i campi del 2o step
        ]);
    }

    /**
     * Salva i risultati del Test C.
     */
    public function store(Request $request, Acceptance $acceptance)
    {
        if (!in_array('test3', $acceptance->tests) || $acceptance->testCResult()->exists()) {
            abort(403, 'Azione non permessa.');
        }

        $validatedData = $this->validateRequest($request, false);

        $dataToSave = $this->prepareData($validatedData, $request);
        $dataToSave['acceptance_id'] = $acceptance->id;
        $dataToSave['operator_id'] = Session::get('user')['id'];
        
        // Recuperiamo gli ID e i lotti delle piastre dall'accettazione
        $is_double_test_c = in_array('test3', $acceptance->double_tests ?? []);
        $acceptance_plates_data = $acceptance->plates ?? [];        

        $get_plate_info = function($index) use ($acceptance_plates_data) {
            $data = $acceptance_plates_data[$index] ?? null;
            if (is_array($data)) return ['id' => $data['id'] ?? null, 'lot' => $data['lot'] ?? null];
            return ['id' => $data, 'lot' => null];
        };

        // Mappa gli ID e i lotti delle piastre (lette dall'accettazione) ai campi corretti del database per il Run 1
        $dataToSave['plate_id_start_lotto'] = $get_plate_info(28)['id'];
        $dataToSave['plate_id_mid_lotto'] = $get_plate_info(29)['id'];
        $dataToSave['plate_id_end_lotto'] = $get_plate_info(30)['id'];
        $dataToSave['plate_id_control_blank'] = $get_plate_info(31)['id'];
        $dataToSave['tsa_sheep_blood_plate_id'] = $get_plate_info(36)['id'];
        $dataToSave['tsa_sheep_blood_plate_lot'] = $get_plate_info(36)['lot'];

        if ($is_double_test_c) {
            // Mappa gli ID e i lotti delle piastre (lette dall'accettazione) ai campi corretti del database per il Run 2
            $dataToSave['plate_id_start_lotto_run2'] = $get_plate_info(32)['id'];
            $dataToSave['plate_id_mid_lotto_run2'] = $get_plate_info(33)['id'];
            $dataToSave['plate_id_end_lotto_run2'] = $get_plate_info(34)['id'];
            $dataToSave['plate_id_control_blank_run2'] = $get_plate_info(35)['id'];
            $dataToSave['tsa_sheep_blood_plate_id_run2'] = $get_plate_info(37)['id'];
            $dataToSave['tsa_sheep_blood_plate_lot_run2'] = $get_plate_info(37)['lot'];
        }

        // Assicura che tutti i campi fillable non forniti nella creazione iniziale siano impostati a null.
        $model = new TestCResult();
        $allFillable = $model->getFillable();
        foreach ($allFillable as $fillableKey) {
            if (!array_key_exists($fillableKey, $dataToSave)) {
                $dataToSave[$fillableKey] = null;
            }
        }

        TestCResult::create($dataToSave);

        // --- Generazione URL Google Calendar ---
        $reminderDays = env('TEST_C_INCUBATION_REMINDER_DAYS', 7);
        $startDate = \Carbon\Carbon::parse($dataToSave['test_start_datetime']);
        $reminderDate = $startDate->copy()->addDays($reminderDays);

        $eventStartDate = $reminderDate->format('Ymd');
        $eventEndDate = $reminderDate->copy()->addDay()->format('Ymd'); // All-day event

        $title = urlencode("Completamento Test C - Lotto: {$acceptance->lotto}");
        $details = urlencode(
            "Promemoria per completare il Test C (MA_60_Valutazione produttività XLD) per l'accettazione N. {$acceptance->acceptance_number}.\n\n" .
            "Link all'applicazione: " . route('acceptance.index')
        );

        $calendarUrl = "https://www.google.com/calendar/render?action=TEMPLATE&text={$title}&dates={$eventStartDate}/{$eventEndDate}&details={$details}";

        return redirect()->route('acceptance.index', ['highlight' => $acceptance->id])
            ->with('success', 'Risultati iniziali del Test C salvati con successo!')
            ->with('calendarUrl', $calendarUrl);
    }

    /**
     * Mostra il form per modificare i risultati del Test C.
     */
    public function edit(TestCResult $test_c_result)
    {
        $currentUser = Session::get('user');
        $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;
        $is_completion_phase = is_null($test_c_result->test_end_datetime);
        // Il form è in sola lettura se l'utente non è un tecnico di laboratorio,
        // o se il test è stato firmato/validato.
        $is_readonly = !$isLabTechnician || $test_c_result->lab_signed_at || $test_c_result->rl_signed_at;

        // --- Inizio blocco recupero utenti via API ---
        $usersMap = [];
        try { // Assicurati che il try inizi qui
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
                 Log::error("API call to get users failed in TestCController@edit with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
             }
        } catch (ConnectionException $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API in TestCController@edit (Connection Error): " . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Errore inatteso durante il recupero della lista utenti dall'API in TestCController@edit: " . $e->getMessage());
        }

        $acceptance = $test_c_result->acceptance;

        $is_double_test_c = in_array('test3', $acceptance->double_tests ?? []);

        $selected_plates = [
            'start_lotto' => ['id' => $test_c_result->plate_id_start_lotto, 'lot' => null],
            'mid_lotto' => ['id' => $test_c_result->plate_id_mid_lotto, 'lot' => null],
            'end_lotto' => ['id' => $test_c_result->plate_id_end_lotto, 'lot' => null],
            'control_blank' => ['id' => $test_c_result->plate_id_control_blank, 'lot' => null],
            'tsa_sheep_blood' => ['id' => $test_c_result->tsa_sheep_blood_plate_id, 'lot' => $test_c_result->tsa_sheep_blood_plate_lot],
        ];

        $selected_plates_run2 = [
            'start_lotto' => ['id' => $test_c_result->plate_id_start_lotto_run2, 'lot' => null],
            'mid_lotto' => ['id' => $test_c_result->plate_id_mid_lotto_run2, 'lot' => null],
            'end_lotto' => ['id' => $test_c_result->plate_id_end_lotto_run2, 'lot' => null],
            'control_blank' => ['id' => $test_c_result->plate_id_control_blank_run2, 'lot' => null],
            'tsa_sheep_blood' => ['id' => $test_c_result->tsa_sheep_blood_plate_id_run2, 'lot' => $test_c_result->tsa_sheep_blood_plate_lot_run2],
        ];

        // The plate IDs and lots are now stored directly in test_c_results, so no fallback to acceptance plates is needed here.

        $incubators = InstrumentItem::whereHas('instrument', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['incubatore']);
        })->get();

        $pipettes = InstrumentItem::whereHas('instrument', function ($query) {
            $query->whereRaw('LOWER(name) = ?', ['pipetta']);
        })->get();

        return view('tests.test_c.create', [
            'acceptance' => $acceptance,
            'test_c_result' => $test_c_result,
            'currentUser' => $currentUser,
            'is_readonly' => $is_readonly,
            'is_double_test_c' => $is_double_test_c,
            'selected_plates' => $selected_plates,
            'selected_plates_run2' => $selected_plates_run2,
            'incubators' => $incubators,
            'pipettes' => $pipettes,
            'usersMap' => $usersMap,
            'methodRevisions' => MethodRevision::all()->keyBy('method_key'),
            'is_completion_phase' => $is_completion_phase,
        ]);
    }

    /**
     * Aggiorna i risultati del Test C.
     */
    public function update(Request $request, TestCResult $test_c_result)
    {
        $currentUser = Session::get('user');
        $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;

        // Policy: non si può modificare se non si è un tecnico di laboratorio, o se il test è firmato/validato.
        if (!$isLabTechnician || $test_c_result->lab_signed_at || $test_c_result->rl_signed_at) {
            abort(403, 'Azione non autorizzata: solo i tecnici di laboratorio possono modificare un test non firmato/validato.');
        }

        $validatedData = $this->validateRequest($request, true);
        $dataToSave = $this->prepareData($validatedData, $request);
        if (isset($validatedData['modification_reason'])) {
            $dataToSave['modification_reason'] = $validatedData['modification_reason'];
        }

        $test_c_result->update($dataToSave);

        return redirect()->route('acceptance.index', ['highlight' => $test_c_result->acceptance_id])->with('success', 'Risultati del Test C aggiornati con successo!');
    }

    /**
     * Appone la firma del tecnico di laboratorio al test.
     */
    public function sign(Request $request, TestCResult $test_c_result)
    {
        $currentUser = Session::get('user');
        
        // Policy 1: Solo i tecnici di laboratorio (ruolo 3) possono firmare.
        $isLabTechnician = isset($currentUser['user17025']) && $currentUser['user17025'] == 3;
        if (!$isLabTechnician) {
            abort(403, 'Azione non autorizzata: solo i tecnici di laboratorio possono firmare i test.');
        }

        // Policy 3: Il test non deve essere già firmato o validato.
        if ($test_c_result->lab_signed_at && !$test_c_result->rl_signed_at) { // Aggiunto controllo per non bloccare se già validato
            return redirect()->route('acceptance.index')->with('error', 'Il test è già stato firmato.');
        }
        if ($test_c_result->rl_signed_at) {
            abort(403, 'Azione non autorizzata: il test è già stato validato e non può essere firmato.');
        }

        // Aggiorna il record con i dati della firma
        $test_c_result->lab_signature_id = $currentUser['id'];
        $test_c_result->lab_signed_at = now();
        $test_c_result->save();

        // --- Invia notifica email ai RL ---
        $rlEmails = $this->getRlEmails();
        if (empty($rlEmails)) {
            Session::flash('notification_warning', 'ATTENZIONE: Nessun Responsabile di Laboratorio con email valida trovato. La notifica non è stata inviata.');
        } else {
            $operatorName = $currentUser['operatore'] ?? 'N/D';
            $testType = 'Test C - MA_60_Valutazione produttività XLD';
            $acceptance = $test_c_result->acceptance;
            try {
                Mail::to($rlEmails)->send(new TestSignedForValidation($test_c_result, $acceptance, $operatorName, $testType));
                Session::flash('notification_success', 'Notifica di validazione inviata con successo a: ' . implode(', ', $rlEmails));
            } catch (\Exception $e) {
                Log::error("Invio email di notifica fallito per Test C ID {$test_c_result->id}: " . $e->getMessage());
                Session::flash('notification_error', 'ATTENZIONE: La notifica di validazione non è stata inviata. Controllare la configurazione del server di posta. Errore: ' . $e->getMessage());
            }
        }
        // --- Fine notifica ---

        return redirect()->route('acceptance.index', ['highlight' => $test_c_result->acceptance_id])->with('success', 'Test C firmato con successo!');
    }

    /**
     * Valida il test da parte del Responsabile Laboratorio (RL).
     */
    public function validateTest(Request $request, TestCResult $test_c_result)
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
        if (!$test_c_result->lab_signed_at) {
            return redirect()->route('acceptance.index', ['highlight' => $test_c_result->acceptance_id])->with('error', 'Il test non può essere validato perché non è stato ancora firmato dal tecnico.');
        }

        // Policy 3: Il test non deve essere già stato validato.
        if ($test_c_result->rl_signed_at) {
            return redirect()->route('acceptance.index', ['highlight' => $test_c_result->acceptance_id])->with('error', 'Il test è già stato validato.');
        }

        $acceptance = $test_c_result->acceptance;

        // Aggiorna il record con i dati della validazione
        $test_c_result->rl_signature_id = $currentUser['id'];
        $test_c_result->rl_signed_at = now();
        $test_c_result->save();

        // Assicurati che il modello dell'accettazione sia aggiornato prima di controllare lo stato
        $acceptance->refresh();

        // Se il PDF diventa completo dopo questa validazione, gestisci la revisione e l'annullamento.
        if ($acceptance->isPdfComplete()) {
            // Se era stato annullato, questa è una ri-validazione.
            if ($acceptance->annulled_at) {
                $acceptance->increment('pdf_revision_count');
                $acceptance->update(['annulled_at' => null, 'annulment_reason' => null]);
            }
        }

        return redirect()->route('acceptance.index', ['highlight' => $test_c_result->acceptance_id])->with('success', 'Test C validato con successo dal Responsabile Laboratorio!');
    }

    /**
     * Valida la richiesta in ingresso.
     */
    private function validateRequest(Request $request, bool $isUpdate = false): array
    {
        // Determine context
        if ($isUpdate) {
            $test_c_result = $request->route('test_c_result');
            $acceptance = $test_c_result->acceptance;
        } else {
            $acceptance = $request->route('acceptance');
        }
        $is_double_test_c = $acceptance ? in_array('test3', $acceptance->double_tests ?? []) : false;

        // --- Define Rule Groups ---
        $growthRule = ['required', Rule::in(['rilevata', 'non_rilevata'])];

        // Phase 1 Rules
        $initial_rules = [
            'pipette_dilution_1' => 'required|string|max:255',
            'pipette_dilution_2' => 'required|string|max:255',
            'pipette_inoculation' => 'required|string|max:255',
            'incubator' => 'required|string|max:255',
            'incubation_start_date' => 'required|date',
            'incubation_start_time' => 'required|date_format:H:i', // Keep this required for start_datetime calculation
            'temperature' => 'nullable|numeric', // Changed to nullable
        ];

        // Phase 2 Rules
        $completion_rules = [
            'incubation_end_date' => 'required|date|after_or_equal:incubation_start_date',
            'incubation_end_time' => 'required|date_format:H:i',
            'tsa_growth_ufc' => 'nullable|integer|min:0', // New
            'tsa_growth_result' => $growthRule, // UFC on TSA plate
            'growth_result_start_lotto' => $growthRule,
            'ufc_start_lotto' => 'required|integer|min:0',
            'ufc_50_percent_tsa_start_lotto' => 'nullable|boolean',
            'growth_result_mid_lotto' => $growthRule,
            'ufc_mid_lotto' => 'required|integer|min:0',
            'ufc_50_percent_tsa_mid_lotto' => 'nullable|boolean',
            'growth_result_end_lotto' => $growthRule,
            'ufc_end_lotto' => 'required|integer|min:0',
            'ufc_50_percent_tsa_end_lotto' => 'nullable|boolean',
            'growth_result_control_blank' => $growthRule,
            'productivity_result' => 'nullable|string', // This is a textarea, can be nullable
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($is_double_test_c) {
            $initial_rules_run2 = [
                'pipette_dilution_1_run2' => 'required|string|max:255',
                'pipette_dilution_2_run2' => 'required|string|max:255',
                'pipette_inoculation_run2' => 'required|string|max:255',
                'incubator_run2' => 'required|string|max:255',
                'incubation_start_date_run2' => 'required|date',
                'incubation_start_time_run2' => 'required|date_format:H:i', // Keep this required for start_datetime calculation
                'temperature_run2' => 'nullable|numeric', // Changed to nullable
            ];
            $initial_rules = array_merge($initial_rules, $initial_rules_run2);

            $completion_rules_run2 = [
                'incubation_end_date_run2' => 'required|date|after_or_equal:incubation_start_date_run2',
                'incubation_end_time_run2' => 'required|date_format:H:i',
                'tsa_growth_result_run2' => $growthRule,
                'tsa_growth_ufc_run2' => 'nullable|integer|min:0',
                'growth_result_start_lotto_run2' => $growthRule,
                'ufc_start_lotto_run2' => 'required|integer|min:0',
                'ufc_50_percent_tsa_start_lotto_run2' => 'nullable|boolean',
                'growth_result_mid_lotto_run2' => $growthRule,
                'ufc_mid_lotto_run2' => 'required|integer|min:0',
                'ufc_50_percent_tsa_mid_lotto_run2' => 'nullable|boolean',
                'growth_result_end_lotto_run2' => $growthRule,
                'ufc_end_lotto_run2' => 'required|integer|min:0',
                'ufc_50_percent_tsa_end_lotto_run2' => 'nullable|boolean',
                'growth_result_control_blank_run2' => $growthRule,
            ];
            $completion_rules = array_merge($completion_rules, $completion_rules_run2);
        }

        $modification_reason_rule = ['modification_reason' => 'required|string|min:10|max:500'];

        // --- Dynamic Validation Logic ---
        $rules = [];
        if (!$isUpdate) {
            // Case 1: Initial creation
            $rules = $initial_rules;
        } else {
            // --- CASO UPDATE ---
            $editMode = $request->input('edit_mode');
            $test_c_result = $request->route('test_c_result');
            $is_already_complete = !is_null($test_c_result->test_end_datetime);

            if ($editMode === 'initial') {
                // L'utente ha scelto di modificare i dati iniziali.
                // Richiediamo i campi iniziali e la motivazione.
                $rules = array_merge($initial_rules, $modification_reason_rule);
            } elseif ($editMode === 'final') {
                // L'utente ha scelto di compilare/modificare i risultati finali.
                // Richiediamo tutti i campi.
                $rules = array_merge($initial_rules, $completion_rules);
                
                // La motivazione è richiesta solo se si stanno modificando i risultati finali già salvati.
                if ($is_already_complete) {
                    $rules = array_merge($rules, $modification_reason_rule);
                }
            } else {
                // Fallback: se 'edit_mode' non è presente (es. JS disabilitato), usiamo la vecchia logica.
                $is_completing_now = $request->filled('test_end_date');
                if ($is_already_complete) {
                    $rules = array_merge($initial_rules, $completion_rules, $modification_reason_rule);
                } else {
                if ($is_completing_now) {
                    $rules = array_merge($initial_rules, $completion_rules);
                } else {
                    $rules = array_merge($initial_rules, $modification_reason_rule);
                }
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
            'integer' => 'Il campo :attribute deve essere un numero intero.', // New
            'boolean' => 'Il campo :attribute deve essere vero o falso.', // New
            'in' => 'Il valore selezionato per :attribute non è valido.',
            'required_if' => 'Il campo :attribute è obbligatorio quando :other è :value.',
            'modification_reason.min' => 'La motivazione della modifica deve contenere almeno :min caratteri.',

            'outcome.required' => 'L\'esito del test è obbligatorio.',
            'non_compliance_ref.required_if' => 'Il riferimento di non conformità è obbligatorio quando l\'esito è "Non Idoneo".',
            
            // New fields messages
            'ufc_start_lotto.required' => 'Il campo UFC (Inizio Lotto) è obbligatorio.',
            'ufc_mid_lotto.required' => 'Il campo UFC (Metà Lotto) è obbligatorio.',
            'ufc_end_lotto.required' => 'Il campo UFC (Fine Lotto) è obbligatorio.',
            
            // Aggiungere qui altri messaggi personalizzati per i nuovi campi se necessario
        ];

        return $request->validate($rules, $messages);
    }

    /**
     * Prepara i dati combinando date e ore.
     */
    private function prepareData(array $validatedData, Request $request): array
    {
        $data = $validatedData;

        $acceptance = $request->route('acceptance');
        if (!$acceptance) { // Se siamo in update
            $test_c_result = $request->route('test_c_result');
            if ($test_c_result) {
                $acceptance = $test_c_result->acceptance;
            }
        }
        $is_double_test_c = $acceptance ? in_array('test3', $acceptance->double_tests ?? []) : false;

        // Rimuove i campi separati di data/ora che verranno combinati
        $date_time_fields_to_unset = [
            'test_start_date', 'test_start_time', 'test_end_date', 'test_end_time',
            'incubation_start_date', 'incubation_start_time', 'incubation_end_date', 'incubation_end_time',
        ];
        if ($is_double_test_c) {
            $date_time_fields_to_unset = array_merge($date_time_fields_to_unset, [
                'incubation_start_date_run2', 'incubation_start_time_run2', 'incubation_end_date_run2', 'incubation_end_time_run2',
            ]);
        }
        foreach ($date_time_fields_to_unset as $field) {
            unset($data[$field]);
        }
        
        $start_dates = [];
        $end_dates = [];

        $data['incubation_start_datetime'] = ($request->filled('incubation_start_date') && $request->filled('incubation_start_time'))
            ? $request->incubation_start_date . ' ' . $request->incubation_start_time
            : null;
        if ($data['incubation_start_datetime']) $start_dates[] = $data['incubation_start_datetime'];

        $data['incubation_end_datetime'] = ($request->filled('incubation_end_date') && $request->filled('incubation_end_time'))
            ? $request->incubation_end_date . ' ' . $request->incubation_end_time
            : null;
        if ($data['incubation_end_datetime']) $end_dates[] = $data['incubation_end_datetime'];

        if ($is_double_test_c) {
            $data['incubation_start_datetime_run2'] = ($request->filled('incubation_start_date_run2') && $request->filled('incubation_start_time_run2'))
                ? $request->incubation_start_date_run2 . ' ' . $request->incubation_start_time_run2
                : null;
            if ($data['incubation_start_datetime_run2']) $start_dates[] = $data['incubation_start_datetime_run2'];

            $data['incubation_end_datetime_run2'] = ($request->filled('incubation_end_date_run2') && $request->filled('incubation_end_time_run2'))
                ? $request->incubation_end_date_run2 . ' ' . $request->incubation_end_time_run2
                : null;
            if ($data['incubation_end_datetime_run2']) $end_dates[] = $data['incubation_end_datetime_run2'];
        }

        // Imposta test_start_datetime e test_end_datetime in base alle date di incubazione
        if (!empty($start_dates)) $data['test_start_datetime'] = min($start_dates);
        if (!empty($end_dates)) $data['test_end_datetime'] = max($end_dates);
        else $data['test_end_datetime'] = null;

        // Handle checkboxes for boolean fields
        $data['ufc_50_percent_tsa_start_lotto'] = $request->has('ufc_50_percent_tsa_start_lotto') ? 1 : 0;
        $data['ufc_50_percent_tsa_mid_lotto'] = $request->has('ufc_50_percent_tsa_mid_lotto') ? 1 : 0;
        $data['ufc_50_percent_tsa_end_lotto'] = $request->has('ufc_50_percent_tsa_end_lotto') ? 1 : 0;
        if ($is_double_test_c) {
            $data['ufc_50_percent_tsa_start_lotto_run2'] = $request->has('ufc_50_percent_tsa_start_lotto_run2') ? 1 : 0;
            $data['ufc_50_percent_tsa_mid_lotto_run2'] = $request->has('ufc_50_percent_tsa_mid_lotto_run2') ? 1 : 0;
            $data['ufc_50_percent_tsa_end_lotto_run2'] = $request->has('ufc_50_percent_tsa_end_lotto_run2') ? 1 : 0;
        }
        return $data;

        // Set default temperature if not provided
        if (!isset($data['temperature'])) {
            $data['temperature'] = 35;
        }
        if ($is_double_test_c && !isset($data['temperature_run2'])) {
            $data['temperature_run2'] = 35;
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