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

        // Quando si crea, non c'è un test_b_result esistente, quindi passiamo array vuoti per le piastre selezionate
        $selected_plates_run1 = [];
        $selected_plates_run2 = [];

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
        $selected_plates_run1 = [
            'start' => $test_b_result->plate_id_start_run1,
            'mid'   => $test_b_result->plate_id_mid_run1,
            'end'   => $test_b_result->plate_id_end_run1,
        ];
        $selected_plates_run2 = [
            'start' => $test_b_result->plate_id_start_run2,
            'mid'   => $test_b_result->plate_id_mid_run2,
            'end'   => $test_b_result->plate_id_end_run2,
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
            'plate_id_start_run1' => $plateIdRule,
            'plate_id_mid_run1' => $plateIdRule,
            'plate_id_end_run1' => $plateIdRule,
            'incubator_35_run1' => 'nullable|string|max:255',
            'incubation_start_date_35_run1' => 'nullable|date',
            'incubation_start_time_35_run1' => 'nullable|date_format:H:i',
            'incubation_end_date_35_run1' => 'nullable|date|after_or_equal:incubation_start_date_35_run1',
            'incubation_end_time_35_run1' => 'nullable|date_format:H:i',
            'temperature_35_run1' => 'nullable|numeric|min:0|max:50',
            'growth_result_35_start_run1' => $growthRule,
            'growth_result_35_mid_run1' => $growthRule,
            'growth_result_35_end_run1' => $growthRule,
            'incubator_22_run1' => 'nullable|string|max:255',
            'incubation_start_date_22_run1' => 'nullable|date',
            'incubation_start_time_22_run1' => 'nullable|date_format:H:i',
            'incubation_end_date_22_run1' => 'nullable|date|after_or_equal:incubation_start_date_22_run1',
            'incubation_end_time_22_run1' => 'nullable|date_format:H:i',
            'temperature_22_run1' => 'nullable|numeric|min:0|max:50',
            'growth_result_22_start_run1' => $growthRule,
            'growth_result_22_mid_run1' => $growthRule,
            'growth_result_22_end_run1' => $growthRule,
            'outcome' => ['required', Rule::in(['idoneo', 'non_idoneo'])],
            'non_compliance_ref' => 'required_if:outcome,non_idoneo|nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ];

        if ($is_double_test_b) {
            $rules['plate_id_start_run2'] = $plateIdRule;
            $rules['plate_id_mid_run2'] = $plateIdRule;
            $rules['plate_id_end_run2'] = $plateIdRule;
            $rules['incubator_35_run2'] = 'nullable|string|max:255';
            $rules['incubation_start_date_35_run2'] = 'nullable|date';
            $rules['incubation_start_time_35_run2'] = 'nullable|date_format:H:i';
            $rules['incubation_end_date_35_run2'] = 'nullable|date|after_or_equal:incubation_start_date_35_run2';
            $rules['incubation_end_time_35_run2'] = 'nullable|date_format:H:i';
            $rules['temperature_35_run2'] = 'nullable|numeric|min:0|max:50';
            $rules['growth_result_35_start_run2'] = $growthRule;
            $rules['growth_result_35_mid_run2'] = $growthRule;
            $rules['growth_result_35_end_run2'] = $growthRule;
            $rules['incubator_22_run2'] = 'nullable|string|max:255';
            $rules['incubation_start_date_22_run2'] = 'nullable|date';
            $rules['incubation_start_time_22_run2'] = 'nullable|date_format:H:i';
            $rules['incubation_end_date_22_run2'] = 'nullable|date|after_or_equal:incubation_start_date_22_run2';
            $rules['incubation_end_time_22_run2'] = 'nullable|date_format:H:i';
            $rules['temperature_22_run2'] = 'nullable|numeric|min:0|max:50';
            $rules['growth_result_22_start_run2'] = $growthRule;
            $rules['growth_result_22_mid_run2'] = $growthRule;
            $rules['growth_result_22_end_run2'] = $growthRule;
        }

        if ($isUpdate) {
            $rules['modification_reason'] = 'required|string|min:10|max:500';
        }

        return $request->validate($rules);
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