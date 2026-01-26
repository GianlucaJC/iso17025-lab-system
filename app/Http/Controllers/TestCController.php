<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use App\Models\TestCResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
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

        // Recupera gli ID delle piastre per il Test C
        $plate_ids = $acceptance->plates ?? [];

        $selected_plates = [
            'plate_1' => $plate_ids[28] ?? null,
            'plate_2' => $plate_ids[29] ?? null,
            'plate_3' => $plate_ids[30] ?? null,
            'control_blank' => $plate_ids[31] ?? null,
            'control_tsa' => $plate_ids[36] ?? null,
        ];

        $selected_plates_run2 = [
            'plate_1' => $is_double_test_c ? ($plate_ids[32] ?? null) : null,
            'plate_2' => $is_double_test_c ? ($plate_ids[33] ?? null) : null,
            'plate_3' => $is_double_test_c ? ($plate_ids[34] ?? null) : null,
            'control_blank' => $is_double_test_c ? ($plate_ids[35] ?? null) : null,
            'control_tsa' => $is_double_test_c ? ($plate_ids[37] ?? null) : null,
        ];

        return view('tests.test_c.create', [
            'acceptance' => $acceptance,
            'currentUser' => Session::get('user'),
            'is_double_test_c' => $is_double_test_c,
            'selected_plates' => $selected_plates,
            'selected_plates_run2' => $selected_plates_run2,
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

        // Poiché gli ID delle piastre non vengono più inviati dal form, li recuperiamo direttamente dall'accettazione.
        $is_double_test_c = in_array('test3', $acceptance->double_tests ?? []);
        $plate_ids = $acceptance->plates ?? [];

        // Mappa gli ID delle piastre ai campi corretti del database per il Run 1
        $dataToSave['plate_id_1'] = $plate_ids[28] ?? null;
        $dataToSave['plate_id_2'] = $plate_ids[29] ?? null;
        $dataToSave['plate_id_3'] = $plate_ids[30] ?? null;
        $dataToSave['plate_id_control_blank'] = $plate_ids[31] ?? null;
        $dataToSave['plate_id_control_tsa'] = $plate_ids[36] ?? null;

        if ($is_double_test_c) {
            // Mappa gli ID delle piastre ai campi corretti del database per il Run 2
            $dataToSave['plate_id_1_run2'] = $plate_ids[32] ?? null;
            $dataToSave['plate_id_2_run2'] = $plate_ids[33] ?? null;
            $dataToSave['plate_id_3_run2'] = $plate_ids[34] ?? null;
            $dataToSave['plate_id_control_blank_run2'] = $plate_ids[35] ?? null;
            $dataToSave['plate_id_control_tsa_run2'] = $plate_ids[37] ?? null;
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
            'plate_1' => $test_c_result->plate_id_1,
            'plate_2' => $test_c_result->plate_id_2,
            'plate_3' => $test_c_result->plate_id_3,
            'control_blank' => $test_c_result->plate_id_control_blank,
            'control_tsa' => $test_c_result->plate_id_control_tsa,
        ];

        $selected_plates_run2 = [
            'plate_1' => $test_c_result->plate_id_1_run2,
            'plate_2' => $test_c_result->plate_id_2_run2,
            'plate_3' => $test_c_result->plate_id_3_run2,
            'control_blank' => $test_c_result->plate_id_control_blank_run2,
            'control_tsa' => $test_c_result->plate_id_control_tsa_run2,
        ];

        return view('tests.test_c.create', [
            'acceptance' => $acceptance,
            'test_c_result' => $test_c_result,
            'currentUser' => $currentUser,
            'is_readonly' => $is_readonly,
            'is_double_test_c' => $is_double_test_c,
            'selected_plates' => $selected_plates,
            'selected_plates_run2' => $selected_plates_run2,
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
        if ($test_c_result->lab_signed_at) {
            return redirect()->route('acceptance.index')->with('error', 'Il test è già stato firmato.');
        }
        if ($test_c_result->rl_signed_at) {
            abort(403, 'Azione non autorizzata: il test è già stato validato e non può essere firmato.');
        }

        // Aggiorna il record con i dati della firma
        $test_c_result->lab_signature_id = $currentUser['id'];
        $test_c_result->lab_signed_at = now();
        $test_c_result->save();

        return redirect()->route('acceptance.index')->with('success', 'Test C firmato con successo!');
    }

    /**
     * Valida il test da parte del Responsabile Laboratorio (RL).
     */
    public function validateTest(Request $request, TestCResult $test_c_result)
    {
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

        return redirect()->route('acceptance.index')->with('success', 'Test C validato con successo dal Responsabile Laboratorio!');
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
            'growth_result_plate_1' => $growthRule,
            'growth_result_plate_2' => $growthRule,
            'growth_result_plate_3' => $growthRule,
            'growth_result_control_blank' => $growthRule,
            'growth_result_control_tsa' => $growthRule,
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($is_double_test_c) {
            $rules['growth_result_plate_1_run2'] = $growthRule;
            $rules['growth_result_plate_2_run2'] = $growthRule;
            $rules['growth_result_plate_3_run2'] = $growthRule;
            $rules['growth_result_control_blank_run2'] = $growthRule;
            $rules['growth_result_control_tsa_run2'] = $growthRule;
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
            'in' => 'Il valore selezionato per :attribute non è valido.',
            'required_if' => 'Il campo :attribute è obbligatorio quando :other è :value.',
            'modification_reason.min' => 'La motivazione della modifica deve contenere almeno :min caratteri.',

            'test_start_date.required' => 'La data di inizio prova è obbligatoria.',
            'test_start_time.required' => 'L\'ora di inizio prova è obbligatoria.',
            'test_end_date.required' => 'La data di fine prova è obbligatoria.',
            'test_end_date.after_or_equal' => 'La data di fine prova deve essere successiva o uguale alla data di inizio prova.',
            'test_end_time.required' => 'L\'ora di fine prova è obbligatoria.',

            'growth_result_plate_1.required' => 'Il risultato di crescita per la Piastra 1 è obbligatorio.',
            'growth_result_plate_2.required' => 'Il risultato di crescita per la Piastra 2 è obbligatorio.',
            'growth_result_plate_3.required' => 'Il risultato di crescita per la Piastra 3 è obbligatorio.',
            'growth_result_control_blank.required' => 'Il risultato di crescita per il Controllo Bianco è obbligatorio.',
            'growth_result_control_tsa.required' => 'Il risultato di crescita per il Controllo TSA è obbligatorio.',

            'outcome.required' => 'L\'esito del test è obbligatorio.',
            'non_compliance_ref.required_if' => 'Il riferimento di non conformità è obbligatorio quando l\'esito è "Non Idoneo".',

            'growth_result_plate_1_run2.required' => 'Il risultato di crescita per la Piastra 1 (Run 2) è obbligatorio.',
            'growth_result_plate_2_run2.required' => 'Il risultato di crescita per la Piastra 2 (Run 2) è obbligatorio.',
            'growth_result_plate_3_run2.required' => 'Il risultato di crescita per la Piastra 3 (Run 2) è obbligatorio.',
            'growth_result_control_blank_run2.required' => 'Il risultato di crescita per il Controllo Bianco (Run 2) è obbligatorio.',
            'growth_result_control_tsa_run2.required' => 'Il risultato di crescita per il Controllo TSA (Run 2) è obbligatorio.',
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
        unset($data['test_start_date'], $data['test_start_time'], $data['test_end_date'], $data['test_end_time']);

        // Combina in campi datetime
        $data['test_start_datetime'] = $request->test_start_date . ' ' . $request->test_start_time;
        $data['test_end_datetime'] = $request->test_end_date . ' ' . $request->test_end_time;

        return $data;
    }
}