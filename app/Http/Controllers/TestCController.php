<?php

namespace App\Http\Controllers;

use App\Models\InstrumentItem;
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
            $query->where('name', 'Incubatore');
        })->get();


        $pipettes = InstrumentItem::whereHas('instrument', function ($query) {
            $query->where('name', 'Pipetta');
        })->get();

        return view('tests.test_c.create', [
            'acceptance' => $acceptance,
            'currentUser' => Session::get('user'),
            'is_double_test_c' => $is_double_test_c,
            'selected_plates' => $selected_plates,
            'selected_plates_run2' => $selected_plates_run2,
            'incubators' => $incubators,
            'pipettes' => $pipettes,
            'usersMap' => $usersMap,
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

        $validatedData = $this->validateRequest($request);

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

        TestCResult::create($dataToSave);

        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test C salvati con successo!');
    }

    /**
     * Mostra il form per modificare i risultati del Test C.
     */
    public function edit(TestCResult $test_c_result)
    {
        $currentUser = Session::get('user');
        $isOwner = $test_c_result->operator_id === $currentUser['id'];
        $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;
        $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;        
        $is_readonly = $isAdmin || $isLabManager || !$isOwner || $test_c_result->lab_signed_at || $test_c_result->rl_signed_at;

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
                Log::error("API call to get users failed in TestCController@edit with status " . $usersResponse->status() . ". Response: " . $usersResponse->body());
            }
        } catch (ConnectionException $e) {
            Log::error("Impossibile recuperare la lista utenti dall'API in TestCController@edit (Connection Error): " . $e->getMessage());
        } catch (\Throwable $e) {
            Log::error("Errore inatteso durante il recupero della lista utenti dall'API in TestCController@edit: " . $e->getMessage());
        }
        // --- Fine blocco recupero utenti ---

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
            $query->where('name', 'Incubatore');
        })->get();

        $pipettes = InstrumentItem::whereHas('instrument', function ($query) {
            $query->where('name', 'Pipetta');
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
        ]);
    }

    /**
     * Aggiorna i risultati del Test C.
     */
    public function update(Request $request, TestCResult $test_c_result)
    {
        $currentUser = Session::get('user');
        $isOwner = $test_c_result->operator_id === $currentUser['id'];
        $isAdmin = isset($currentUser['user17025']) && $currentUser['user17025'] == 1;
        $isLabManager = isset($currentUser['user17025']) && $currentUser['user17025'] == 4;
        if (!$isOwner || $test_c_result->lab_signed_at || $test_c_result->rl_signed_at || $isAdmin || $isLabManager) {
            abort(403, 'Azione non autorizzata: il test è firmato o validato e non può essere modificato.');
        }

        $validatedData = $this->validateRequest($request, true);
        $dataToSave = $this->prepareData($validatedData, $request);
        $dataToSave['modification_reason'] = $validatedData['modification_reason'];

        $test_c_result->update($dataToSave);

        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test C aggiornati con successo!');
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

        $isOwner = $test_c_result->operator_id === $currentUser['id'];

        // Policy 2: Solo il proprietario del test può firmare.
        if (!$isOwner) {            
            abort(403, 'Azione non autorizzata: solo l\'operatore che ha compilato il test può firmare.');
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
            $testType = 'Test C - Controllo contaminazione microbica';
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

        return redirect()->route('acceptance.index')->with('success', 'Test C firmato con successo!');
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
            return redirect()->route('acceptance.index')->with('error', 'Il test non può essere validato perché non è stato ancora firmato dal tecnico.');
        }

        // Policy 3: Il test non deve essere già stato validato.
        if ($test_c_result->rl_signed_at) {
            return redirect()->route('acceptance.index')->with('error', 'Il test è già stato validato.');
        }

        // Aggiorna il record con i dati della validazione
        $test_c_result->rl_signature_id = $currentUser['id'];
        $test_c_result->rl_signed_at = now();
        $test_c_result->save();

        return redirect()->route('test-c.edit', $test_c_result->id)->with('success', 'Test C validato con successo dal Responsabile Laboratorio!');
    }

    /**
     * Valida la richiesta in ingresso.
     */
    private function validateRequest(Request $request, bool $isUpdate = false): array
    {
        // Regole di validazione
        $growthRule = ['required', Rule::in(['rilevata', 'non_rilevata'])];

        if ($isUpdate) {
            $test_c_result = $request->route('test_c_result');
            $acceptance = $test_c_result->acceptance;
        } else {
            $acceptance = $request->route('acceptance');
        }
        $is_double_test_c = $acceptance ? in_array('test3', $acceptance->double_tests ?? []) : false;

        $rules = [
            'test_start_date' => 'required|date',
            'test_start_time' => 'required|date_format:H:i',
            'test_end_date' => 'required|date|after_or_equal:test_start_date',
            'test_end_time' => 'required|date_format:H:i',

            // Nuovi campi
            // 'tsa_sheep_blood_plate_id' => 'required|string|max:255', // Rimosso perché non è un input del form
            'pipette_dilution_1' => 'required|string|max:255',
            'pipette_dilution_2' => 'required|string|max:255',
            'pipette_inoculation' => 'required|string|max:255',
            'incubator' => 'required|string|max:255',
            'incubation_start_date' => 'required|date',
            'incubation_start_time' => 'required|date_format:H:i',
            'incubation_end_date' => 'required|date|after_or_equal:incubation_start_date',
            'incubation_end_time' => 'required|date_format:H:i',
            'temperature' => 'required|numeric',
            'tsa_growth_ufc' => 'nullable|integer|min:0', // New
            'tsa_growth_result' => $growthRule, // UFC on TSA plate
            'growth_result_start_lotto' => $growthRule,
            'ufc_start_lotto' => 'required|integer|min:0',
            'ufc_50_percent_tsa_start_lotto' => 'accepted',
            'growth_result_mid_lotto' => $growthRule,
            'ufc_mid_lotto' => 'required|integer|min:0',
            'ufc_50_percent_tsa_mid_lotto' => 'accepted',
            'growth_result_end_lotto' => $growthRule,
            'ufc_end_lotto' => 'required|integer|min:0',
            'ufc_50_percent_tsa_end_lotto' => 'accepted',
            'growth_result_control_blank' => $growthRule,
            'productivity_result' => 'nullable|string', // This is a textarea, can be nullable
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($is_double_test_c) {
            // $rules['tsa_sheep_blood_plate_id_run2'] = 'required|string|max:255'; // Rimosso perché non è un input del form
            $rules['pipette_dilution_1_run2'] = 'required|string|max:255';
            $rules['pipette_dilution_2_run2'] = 'required|string|max:255';
            $rules['pipette_inoculation_run2'] = 'required|string|max:255';
            $rules['incubator_run2'] = 'required|string|max:255';
            $rules['incubation_start_date_run2'] = 'required|date';
            $rules['incubation_start_time_run2'] = 'required|date_format:H:i';
            $rules['incubation_end_date_run2'] = 'required|date|after_or_equal:incubation_start_date_run2';
            $rules['incubation_end_time_run2'] = 'required|date_format:H:i';
            $rules['temperature_run2'] = 'required|numeric';
            $rules['tsa_growth_result_run2'] = $growthRule;
            $rules['tsa_growth_ufc_run2'] = 'nullable|integer|min:0';
            $rules['growth_result_start_lotto_run2'] = $growthRule;
            $rules['ufc_start_lotto_run2'] = 'required|integer|min:0';
            $rules['ufc_50_percent_tsa_start_lotto_run2'] = 'accepted';
            $rules['growth_result_mid_lotto_run2'] = $growthRule;
            $rules['ufc_mid_lotto_run2'] = 'required|integer|min:0';
            $rules['ufc_50_percent_tsa_mid_lotto_run2'] = 'accepted';
            $rules['growth_result_end_lotto_run2'] = $growthRule;
            $rules['ufc_end_lotto_run2'] = 'required|integer|min:0';
            $rules['ufc_50_percent_tsa_end_lotto_run2'] = 'accepted';
            $rules['growth_result_control_blank_run2'] = $growthRule;
        }

        if ($isUpdate) {
            $rules['modification_reason'] = 'required|string|min:10|max:500';
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

            'test_start_date.required' => 'La data di inizio prova è obbligatoria.',
            'test_start_time.required' => 'L\'ora di inizio prova è obbligatoria.',
            'test_end_date.required' => 'La data di fine prova è obbligatoria.',
            'test_end_date.after_or_equal' => 'La data di fine prova deve essere successiva o uguale alla data di inizio prova.',
            'test_end_time.required' => 'L\'ora di fine prova è obbligatoria.',

            'outcome.required' => 'L\'esito del test è obbligatorio.',
            'non_compliance_ref.required_if' => 'Il riferimento di non conformità è obbligatorio quando l\'esito è "Non Idoneo".',
            
            // New fields messages
            'ufc_start_lotto.required' => 'Il campo UFC (Inizio Lotto) è obbligatorio.',
            'ufc_mid_lotto.required' => 'Il campo UFC (Metà Lotto) è obbligatorio.',
            'ufc_end_lotto.required' => 'Il campo UFC (Fine Lotto) è obbligatorio.',
            'ufc_50_percent_tsa_start_lotto.accepted' => 'Il campo UFC >=50% (Inizio Lotto) deve essere selezionato.',
            'ufc_50_percent_tsa_mid_lotto.accepted' => 'Il campo UFC >=50% (Metà Lotto) deve essere selezionato.',
            'ufc_50_percent_tsa_end_lotto.accepted' => 'Il campo UFC >=50% (Fine Lotto) deve essere selezionato.',

            'ufc_start_lotto_run2.required' => 'Il campo UFC (Inizio Lotto, Run 2) è obbligatorio.',
            'ufc_mid_lotto_run2.required' => 'Il campo UFC (Metà Lotto, Run 2) è obbligatorio.',
            'ufc_end_lotto_run2.required' => 'Il campo UFC (Fine Lotto, Run 2) è obbligatorio.',
            'ufc_50_percent_tsa_start_lotto_run2.accepted' => 'Il campo UFC >=50% (Inizio Lotto, Run 2) deve essere selezionato.',
            'ufc_50_percent_tsa_mid_lotto_run2.accepted' => 'Il campo UFC >=50% (Metà Lotto, Run 2) deve essere selezionato.',
            'ufc_50_percent_tsa_end_lotto_run2.accepted' => 'Il campo UFC >=50% (Fine Lotto, Run 2) deve essere selezionato.',
            
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
        if (!$acceptance) {
            $test_c_result = $request->route('test_c_result');
            if ($test_c_result) {
                $acceptance = $test_c_result->acceptance;
            }
        }

        $is_double_test_c = $acceptance ? in_array('test3', $acceptance->double_tests ?? []) : false;

        // Rimuove i campi separati di data/ora
        // Global test start/end datetimes are handled once
        unset($data['test_start_date'], $data['test_start_time'], $data['test_end_date'], $data['test_end_time']);

        // Incubation datetimes are per run
        unset($data['incubation_start_date'], $data['incubation_start_time'], $data['incubation_end_date'], $data['incubation_end_time']);
        // Combina in campi datetime
        $data['test_start_datetime'] = $request->test_start_date . ' ' . $request->test_start_time;
        $data['test_end_datetime'] = $request->test_end_date . ' ' . $request->test_end_time;
        $data['incubation_start_datetime'] = $request->incubation_start_date . ' ' . $request->incubation_start_time;
        $data['incubation_end_datetime'] = $request->incubation_end_date . ' ' . $request->incubation_end_time;

        if ($is_double_test_c) {
            unset(
                $data['incubation_start_date_run2'], $data['incubation_start_time_run2'],
                $data['incubation_end_date_run2'], $data['incubation_end_time_run2']
            );
            $data['incubation_start_datetime_run2'] = $request->incubation_start_date_run2 . ' ' . $request->incubation_start_time_run2;
            $data['incubation_end_datetime_run2'] = $request->incubation_end_date_run2 . ' ' . $request->incubation_end_time_run2;
        }

        // Handle checkboxes for boolean fields
        $data['ufc_50_percent_tsa_start_lotto'] = $request->has('ufc_50_percent_tsa_start_lotto');
        $data['ufc_50_percent_tsa_mid_lotto'] = $request->has('ufc_50_percent_tsa_mid_lotto');
        $data['ufc_50_percent_tsa_end_lotto'] = $request->has('ufc_50_percent_tsa_end_lotto');
        if ($is_double_test_c) {
            $data['ufc_50_percent_tsa_start_lotto_run2'] = $request->has('ufc_50_percent_tsa_start_lotto_run2');
            $data['ufc_50_percent_tsa_mid_lotto_run2'] = $request->has('ufc_50_percent_tsa_mid_lotto_run2');
            $data['ufc_50_percent_tsa_end_lotto_run2'] = $request->has('ufc_50_percent_tsa_end_lotto_run2');
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