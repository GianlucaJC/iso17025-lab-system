<?php

namespace App\Http\Controllers;

use App\Models\Acceptance;
use App\Models\TestBResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

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

        $is_double_test_b = in_array('test2', $acceptance->double_tests ?? []);

        // Recupera gli ID delle piastre per il Test B
        $plate_ids = $acceptance->plates ?? [];
        $available_plates_run1 = array_slice($plate_ids, 4, 12); // Test B standard: 12 plates from index 4
        $available_plates_run2 = $is_double_test_b ? array_slice($plate_ids, 16, 12) : []; // Test B double: 12 plates from index 16

        // Quando si crea, pre-popoliamo i valori di default in base all'ordine di accettazione per 35C e 22C
        $selected_plates_run1 = [
            '35' => [
                'start_plate1' => $available_plates_run1[0] ?? null,
                'start_plate2' => $available_plates_run1[1] ?? null,
                'mid_plate1'   => $available_plates_run1[2] ?? null,
                'mid_plate2'   => $available_plates_run1[3] ?? null,
                'end_plate1'   => $available_plates_run1[4] ?? null,
                'end_plate2'   => $available_plates_run1[5] ?? null,
            ],
            '22' => [
                'start_plate1' => $available_plates_run1[6] ?? null,
                'start_plate2' => $available_plates_run1[7] ?? null,
                'mid_plate1'   => $available_plates_run1[8] ?? null,
                'mid_plate2'   => $available_plates_run1[9] ?? null,
                'end_plate1'   => $available_plates_run1[10] ?? null,
                'end_plate2'   => $available_plates_run1[11] ?? null,
            ],
        ];
        $selected_plates_run2 = [
            '35' => [
                'start_plate1' => $available_plates_run2[0] ?? null,
                'start_plate2' => $available_plates_run2[1] ?? null,
                'mid_plate1'   => $available_plates_run2[2] ?? null,
                'mid_plate2'   => $available_plates_run2[3] ?? null,
                'end_plate1'   => $available_plates_run2[4] ?? null,
                'end_plate2'   => $available_plates_run2[5] ?? null,
            ],
            '22' => [
                'start_plate1' => $available_plates_run2[6] ?? null,
                'start_plate2' => $available_plates_run2[7] ?? null,
                'mid_plate1'   => $available_plates_run2[8] ?? null,
                'mid_plate2'   => $available_plates_run2[9] ?? null,
                'end_plate1'   => $available_plates_run2[10] ?? null,
                'end_plate2'   => $available_plates_run2[11] ?? null,
            ],
        ];
        $test_b_plates = []; // Questa variabile non è più usata direttamente per la visualizzazione

        return view('tests.test_b.create', [
            'acceptance' => $acceptance,
            'currentUser' => Session::get('user'),
            'test_b_plates' => $test_b_plates,
            'is_double_test_b' => $is_double_test_b,
            'available_plates_run1' => $available_plates_run1,
            'available_plates_run2' => $available_plates_run2,
            'selected_plates_run1' => $selected_plates_run1,
            'selected_plates_run2' => $selected_plates_run2,
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

        $validatedData = $this->validateRequest($request);

        $dataToSave = $this->prepareData($validatedData, $request);
        $dataToSave['acceptance_id'] = $acceptance->id;
        $dataToSave['operator_id'] = Session::get('user')['id'];

        TestBResult::create($dataToSave);

        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test B salvati con successo!');
    }

    /**
     * Mostra il form per modificare i risultati del Test B.
     */
    public function edit(TestBResult $test_b_result)
    {
        $currentUser = Session::get('user');
        $isOwner = $test_b_result->operator_id === $currentUser['id'];
        $is_readonly = !$isOwner || $test_b_result->validator_id;

        $acceptance = $test_b_result->acceptance;

        $is_double_test_b = in_array('test2', $acceptance->double_tests ?? []);

        // Recupera gli ID delle piastre per il Test B
        $plate_ids = $acceptance->plates ?? [];
        $available_plates_run1 = array_slice($plate_ids, 4, 12); // Test B standard: 12 plates from index 4
        $available_plates_run2 = $is_double_test_b ? array_slice($plate_ids, 16, 12) : []; // Test B double: 12 plates from index 16

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

        return view('tests.test_b.create', [
            'acceptance' => $acceptance,
            'test_b_result' => $test_b_result,
            'currentUser' => $currentUser,
            'is_readonly' => $is_readonly,
            'test_b_plates' => $test_b_plates,
            'is_double_test_b' => $is_double_test_b,
            'available_plates_run1' => $available_plates_run1,
            'available_plates_run2' => $available_plates_run2,
            'selected_plates_run1' => $selected_plates_run1,
            'selected_plates_run2' => $selected_plates_run2,
        ]);
    }

    /**
     * Aggiorna i risultati del Test B.
     */
    public function update(Request $request, TestBResult $test_b_result)
    {
        $currentUser = Session::get('user');
        if ($test_b_result->operator_id !== $currentUser['id'] || $test_b_result->validator_id) {
            abort(403, 'Azione non autorizzata.');
        }

        $validatedData = $this->validateRequest($request, true);
        $dataToSave = $this->prepareData($validatedData, $request);
        $dataToSave['modification_reason'] = $validatedData['modification_reason'];

        $test_b_result->update($dataToSave);

        return redirect()->route('acceptance.index')->with('success', 'Risultati del Test B aggiornati con successo!');
    }

    /**
     * Valida la richiesta in ingresso.
     */
    private function validateRequest(Request $request, bool $isUpdate = false): array
    {
        // Regole di validazione
        $growthRule = ['nullable', Rule::in(['rilevata', 'non_rilevata'])];
        $plateIdRule = 'required|numeric';

        if ($isUpdate) {
            // Per l'aggiornamento, il modello TestBResult è nella rotta
            $test_b_result = $request->route('test_b_result');
            $acceptance = $test_b_result->acceptance;
        } else {
            // Per la creazione, il modello Acceptance è nella rotta
            $acceptance = $request->route('acceptance');
        }
        $is_double_test_b = $acceptance ? in_array('test2', $acceptance->double_tests ?? []) : false;

        $rules = [
            'test_start_date' => 'required|date',
            'test_start_time' => 'required|date_format:H:i',
            'test_end_date' => 'required|date|after_or_equal:test_start_date',
            'test_end_time' => 'required|date_format:H:i',
            'plate_id_start_plate1_35_run1' => $plateIdRule,
            'plate_id_start_plate2_35_run1' => $plateIdRule,
            'plate_id_mid_plate1_35_run1' => $plateIdRule,
            'plate_id_mid_plate2_35_run1' => $plateIdRule,
            'plate_id_end_plate1_35_run1' => $plateIdRule,
            'plate_id_end_plate2_35_run1' => $plateIdRule,
            'incubator_35_run1' => 'nullable|string|max:255',
            'incubation_start_date_35_run1' => 'nullable|date',
            'incubation_start_time_35_run1' => 'nullable|date_format:H:i',
            'incubation_end_date_35_run1' => 'nullable|date|after_or_equal:incubation_start_date_35_run1',
            'incubation_end_time_35_run1' => 'nullable|date_format:H:i',
            'temperature_35_run1' => 'nullable|numeric|min:0|max:50',
            'growth_result_35_start_plate1_run1' => $growthRule, // Growth rules for 35C
            'growth_result_35_start_plate2_run1' => $growthRule,
            'growth_result_35_mid_plate1_run1' => $growthRule,
            'growth_result_35_mid_plate2_run1' => $growthRule,
            'growth_result_35_end_plate1_run1' => $growthRule,
            'growth_result_35_end_plate2_run1' => $growthRule,
            'plate_id_start_plate1_22_run1' => $plateIdRule, // Plates for 22C
            'plate_id_start_plate2_22_run1' => $plateIdRule,
            'plate_id_mid_plate1_22_run1' => $plateIdRule,
            'plate_id_mid_plate2_22_run1' => $plateIdRule,
            'plate_id_end_plate1_22_run1' => $plateIdRule,
            'plate_id_end_plate2_22_run1' => $plateIdRule, 
            'incubator_22_run1' => 'required|string|max:255', // Dati Incubazione
            'incubation_start_date_22_run1' => 'required|date', // Dati Incubazione
            'incubation_start_time_22_run1' => 'required|date_format:H:i', // Dati Incubazione
            'incubation_end_date_22_run1' => 'required|date|after_or_equal:incubation_start_date_22_run1', // Dati Incubazione
            'incubation_end_time_22_run1' => 'required|date_format:H:i', // Dati Incubazione
            'temperature_22_run1' => 'required|numeric|min:0|max:50', // Dati Incubazione
            'growth_result_22_start_plate1_run1' => $growthRule, // Growth rules for 22C
            'growth_result_22_start_plate2_run1' => $growthRule,
            'growth_result_22_mid_plate1_run1' => $growthRule,
            'growth_result_22_mid_plate2_run1' => $growthRule,
            'growth_result_22_end_plate1_run1' => $growthRule,
            'growth_result_22_end_plate2_run1' => $growthRule,
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];


        if ($is_double_test_b) {
            $rules['plate_id_start_plate1_35_run2'] = $plateIdRule; // Plates for 35C
            $rules['plate_id_start_plate1_35_run2'] = $plateIdRule;
            $rules['plate_id_start_plate2_35_run2'] = $plateIdRule;
            $rules['plate_id_mid_plate1_35_run2'] = $plateIdRule;
            $rules['plate_id_mid_plate2_35_run2'] = $plateIdRule;
            $rules['plate_id_end_plate1_35_run2'] = $plateIdRule;
            $rules['plate_id_end_plate2_35_run2'] = $plateIdRule;
            $rules['incubator_35_run2'] = 'required|string|max:255';
            $rules['incubation_start_date_35_run2'] = 'required|date';
            $rules['incubation_start_time_35_run2'] = 'required|date_format:H:i';
            $rules['incubation_end_date_35_run2'] = 'required|date|after_or_equal:incubation_start_date_35_run2';
            $rules['incubation_end_time_35_run2'] = 'required|date_format:H:i';
            $rules['temperature_35_run2'] = 'required|numeric|min:0|max:50';
            $rules['growth_result_35_start_plate1_run2'] = $growthRule;
            $rules['growth_result_35_start_plate2_run2'] = $growthRule;
            $rules['growth_result_35_mid_plate1_run2'] = $growthRule;
            $rules['growth_result_35_mid_plate2_run2'] = $growthRule;
            $rules['growth_result_35_end_plate1_run2'] = $growthRule;
            $rules['growth_result_35_end_plate2_run2'] = $growthRule;

            $rules['plate_id_start_plate1_22_run2'] = $plateIdRule;
            $rules['plate_id_start_plate2_22_run2'] = $plateIdRule;
            $rules['plate_id_mid_plate1_22_run2'] = $plateIdRule;
            $rules['plate_id_mid_plate2_22_run2'] = $plateIdRule;
            $rules['plate_id_end_plate1_22_run2'] = $plateIdRule;
            $rules['plate_id_end_plate2_22_run2'] = $plateIdRule;
            $rules['incubator_22_run2'] = 'required|string|max:255';
            $rules['incubation_start_date_22_run2'] = 'required|date';
            $rules['incubation_start_time_22_run2'] = 'required|date_format:H:i';
            $rules['incubation_end_date_22_run2'] = 'required|date|after_or_equal:incubation_start_date_22_run2';
            $rules['incubation_end_time_22_run2'] = 'required|date_format:H:i';
            $rules['temperature_22_run2'] = 'required|numeric|min:0|max:50';
            $rules['growth_result_22_start_plate1_run2'] = $growthRule;
            $rules['growth_result_22_start_plate2_run2'] = $growthRule;
            $rules['growth_result_22_mid_plate1_run2'] = $growthRule;
            $rules['growth_result_22_mid_plate2_run2'] = $growthRule;
            $rules['growth_result_22_end_plate1_run2'] = $growthRule;
            $rules['growth_result_22_end_plate2_run2'] = $growthRule;
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

            // Messaggi specifici per i campi
            'test_start_date.required' => 'La data di inizio prova è obbligatoria.',
            'test_start_time.required' => 'L\'ora di inizio prova è obbligatoria.',
            'test_end_date.required' => 'La data di fine prova è obbligatoria.',
            'test_end_date.after_or_equal' => 'La data di fine prova deve essere successiva o uguale alla data di inizio prova.',
            'test_end_time.required' => 'L\'ora di fine prova è obbligatoria.',

            'plate_id_start_plate1_35_run1.required' => 'L\'ID della piastra di inizio (P1, 35°C, Run 1) è obbligatorio.',
            'plate_id_start_plate2_35_run1.required' => 'L\'ID della piastra di inizio (P2, 35°C, Run 1) è obbligatorio.',
            'plate_id_mid_plate1_35_run1.required' => 'L\'ID della piastra di metà (P1, 35°C, Run 1) è obbligatorio.',
            'plate_id_mid_plate2_35_run1.required' => 'L\'ID della piastra di metà (P2, 35°C, Run 1) è obbligatorio.',
            'plate_id_end_plate1_35_run1.required' => 'L\'ID della piastra di fine (P1, 35°C, Run 1) è obbligatorio.',
            'plate_id_end_plate2_35_run1.required' => 'L\'ID della piastra di fine (P2, 35°C, Run 1) è obbligatorio.',
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

            'plate_id_start_plate1_22_run1.required' => 'L\'ID della piastra di inizio (P1, 22°C, Run 1) è obbligatorio.',
            'plate_id_start_plate2_22_run1.required' => 'L\'ID della piastra di inizio (P2, 22°C, Run 1) è obbligatorio.',
            'plate_id_mid_plate1_22_run1.required' => 'L\'ID della piastra di metà (P1, 22°C, Run 1) è obbligatorio.',
            'plate_id_mid_plate2_22_run1.required' => 'L\'ID della piastra di metà (P2, 22°C, Run 1) è obbligatorio.',
            'plate_id_end_plate1_22_run1.required' => 'L\'ID della piastra di fine (P1, 22°C, Run 1) è obbligatorio.',
            'plate_id_end_plate2_22_run1.required' => 'L\'ID della piastra di fine (P2, 22°C, Run 1) è obbligatorio.',
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
            'plate_id_start_plate1_35_run2.required' => 'L\'ID della piastra di inizio (P1, 35°C, Run 2) è obbligatorio.',
            'plate_id_start_plate2_35_run2.required' => 'L\'ID della piastra di inizio (P2, 35°C, Run 2) è obbligatorio.',
            'plate_id_mid_plate1_35_run2.required' => 'L\'ID della piastra di metà (P1, 35°C, Run 2) è obbligatorio.',
            'plate_id_mid_plate2_35_run2.required' => 'L\'ID della piastra di metà (P2, 35°C, Run 2) è obbligatorio.',
            'plate_id_end_plate1_35_run2.required' => 'L\'ID della piastra di fine (P1, 35°C, Run 2) è obbligatorio.',
            'plate_id_end_plate2_35_run2.required' => 'L\'ID della piastra di fine (P2, 35°C, Run 2) è obbligatorio.',
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

            'plate_id_start_plate1_22_run2.required' => 'L\'ID della piastra di inizio (P1, 22°C, Run 2) è obbligatorio.',
            'plate_id_start_plate2_22_run2.required' => 'L\'ID della piastra di inizio (P2, 22°C, Run 2) è obbligatorio.',
            'plate_id_mid_plate1_22_run2.required' => 'L\'ID della piastra di metà (P1, 22°C, Run 2) è obbligatorio.',
            'plate_id_mid_plate2_22_run2.required' => 'L\'ID della piastra di metà (P2, 22°C, Run 2) è obbligatorio.',
            'plate_id_end_plate1_22_run2.required' => 'L\'ID della piastra di fine (P1, 22°C, Run 2) è obbligatorio.',
            'plate_id_end_plate2_22_run2.required' => 'L\'ID della piastra di fine (P2, 22°C, Run 2) è obbligatorio.',
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
        $data['test_end_datetime'] = $request->test_end_date . ' ' . $request->test_end_time;

        // Handle run 1 incubation datetimes
        if ($request->incubation_start_date_35_run1 && $request->incubation_start_time_35_run1) {
            $data['incubation_start_datetime_35_run1'] = $request->incubation_start_date_35_run1 . ' ' . $request->incubation_start_time_35_run1;
        } else {
            $data['incubation_start_datetime_35_run1'] = null;
        }
        if ($request->incubation_end_date_35_run1 && $request->incubation_end_time_35_run1) {
            $data['incubation_end_datetime_35_run1'] = $request->incubation_end_date_35_run1 . ' ' . $request->incubation_end_time_35_run1;
        } else {
            $data['incubation_end_datetime_35_run1'] = null;
        }
        if ($request->incubation_start_date_22_run1 && $request->incubation_start_time_22_run1) {
            $data['incubation_start_datetime_22_run1'] = $request->incubation_start_date_22_run1 . ' ' . $request->incubation_start_time_22_run1;
        } else {
            $data['incubation_start_datetime_22_run1'] = null;
        }
        if ($request->incubation_end_date_22_run1 && $request->incubation_end_time_22_run1) {
            $data['incubation_end_datetime_22_run1'] = $request->incubation_end_date_22_run1 . ' ' . $request->incubation_end_time_22_run1;
        } else {
            $data['incubation_end_datetime_22_run1'] = null;
        }

        // Handle run 2 incubation datetimes if it's a double test
        if ($is_double_test_b) {
            if ($request->incubation_start_date_35_run2 && $request->incubation_start_time_35_run2) {
                $data['incubation_start_datetime_35_run2'] = $request->incubation_start_date_35_run2 . ' ' . $request->incubation_start_time_35_run2;
            } else {
                $data['incubation_start_datetime_35_run2'] = null;
            }
            if ($request->incubation_end_date_35_run2 && $request->incubation_end_time_35_run2) {
                $data['incubation_end_datetime_35_run2'] = $request->incubation_end_date_35_run2 . ' ' . $request->incubation_end_time_35_run2;
            } else {
                $data['incubation_end_datetime_35_run2'] = null;
            }
            if ($request->incubation_start_date_22_run2 && $request->incubation_start_time_22_run2) {
                $data['incubation_start_datetime_22_run2'] = $request->incubation_start_date_22_run2 . ' ' . $request->incubation_start_time_22_run2;
            } else {
                $data['incubation_start_datetime_22_run2'] = null;
            }
            if ($request->incubation_end_date_22_run2 && $request->incubation_end_time_22_run2) {
                $data['incubation_end_datetime_22_run2'] = $request->incubation_end_date_22_run2 . ' ' . $request->incubation_end_time_22_run2;
            } else {
                $data['incubation_end_datetime_22_run2'] = null;
            }
        }

        return $data;
    }
}