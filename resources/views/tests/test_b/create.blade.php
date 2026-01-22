<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    @php
        $is_edit = isset($test_b_result);
        $is_readonly = $is_readonly ?? false;
        $form_title = $is_edit ? ($is_readonly ? 'Visualizza Risultati' : 'Modifica Risultati') : 'Esecuzione';
    @endphp
    <title>{{ $form_title }} Test B - Produttività</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom width for the main content area */
        main.container {
            max-width: 1340px; /* Default Bootstrap container-xl is 1140px, adding 200px */
        }
        /* Adjust for larger screens (xxl breakpoint and above) */
        @media (min-width: 1400px) {
            main.container {
                max-width: 1520px; /* Default Bootstrap container-xxl is 1320px, adding 200px */
            }
        }
    </style>
    {{-- Dipendenza per SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <div>
                <a href="{{ route('acceptance.create') }}" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i>Nuova Accettazione</a>
                <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
            </div>
            <div class="d-flex align-items-center">
                @if(Session::has('user'))
                    @php
                        $user = Session::get('user');
                        $roleId = $user['user17025'] ?? null;
                        $roleMap = [1 => 'Admin', 3 => 'Tecnico di Laboratorio', 4 => 'Responsabile Laboratorio'];
                        $badgeColorMap = [1 => 'bg-danger', 3 => 'bg-primary', 4 => 'bg-success'];
                        $roleName = $roleMap[$roleId] ?? 'N/D';
                        $badgeColor = $badgeColorMap[$roleId] ?? 'bg-secondary';
                    @endphp
                    <span class="navbar-text me-3">
                        <i class="fas fa-user me-1"></i>
                        {{ $user['operatore'] ?? $user['username'] }}
                        <span class="badge rounded-pill {{ $badgeColor }} ms-1">{{ $roleName }}</span>
                    </span>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-sign-out-alt me-2"></i>Logout</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container mt-4 flex-grow-1">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header {{ $is_edit && !$is_readonly ? 'bg-warning' : 'bg-primary text-white' }}">
                <h3>
                    @if($is_edit)
                        @if($is_readonly) <i class="fas fa-eye me-2"></i>Visualizza @else <i class="fas fa-edit me-2"></i>Modifica @endif
                    @else
                        <i class="fas fa-vial me-2"></i>Esecuzione
                    @endif
                    Test B: Produttività, Metodo Qualitativo
                </h3>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ $is_edit ? route('test-b.update', $test_b_result->id) : route('test-b.store', $acceptance->id) }}" class="needs-validation" novalidate>
                    @csrf
                    @if($is_edit) @method('PUT') @endif

                    {{-- Dati di Riferimento --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Dati di Riferimento</legend>
                        <div class="row p-3 bg-light border rounded">
                            <div class="col-md-4"><label class="form-label fw-bold">Lotto</label><p class="form-control-plaintext">{{ $acceptance->lotto }}</p></div>
                            <div class="col-md-4"><label class="form-label fw-bold">N. Accettazione</label><p class="form-control-plaintext">{{ $acceptance->acceptance_number }}</p></div>
                            <div class="col-md-4"><label class="form-label fw-bold">Operatore</label><p class="form-control-plaintext">{{ $currentUser['operatore'] ?? '' }}</p></div>
                        </div>
                    </fieldset>

                    {{-- Dati Generali Prova --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Dati Generali Prova</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Inizio Prova</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="test_start_date" value="{{ old('test_start_date', $is_edit ? $test_b_result->test_start_datetime->format('Y-m-d') : date('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                    <input type="time" class="form-control" name="test_start_time" value="{{ old('test_start_time', $is_edit ? $test_b_result->test_start_datetime->format('H:i') : date('H:i')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fine Prova</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="test_end_date" value="{{ old('test_end_date', $is_edit ? $test_b_result->test_end_datetime->format('Y-m-d') : date('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                    <input type="time" class="form-control" name="test_end_time" value="{{ old('test_end_time', $is_edit ? $test_b_result->test_end_datetime->format('H:i') : date('H:i')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    @php
                        $incubation_types = [
                            '35' => ['label' => '35±2°C', 'color' => 'bg-danger-subtle'],
                            '22' => ['label' => '22±2°C', 'color' => 'bg-primary-subtle']
                        ];
                    @endphp

                    @foreach($incubation_types as $temp => $details)
                    {{-- Incubazione a {{ $details['label'] }} --}}
                    <fieldset class="mb-4 p-3 border rounded {{ $details['color'] }}">
                        <legend class="h6 w-auto px-2">Incubazione a {{ $details['label'] }}</legend>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="incubator_{{ $temp }}_run1" class="form-label">Incubatore</label>
                                <input type="text" class="form-control" id="incubator_{{ $temp }}_run1" name="incubator_{{ $temp }}_run1" value="{{ old('incubator_'.$temp.'_run1', $is_edit ? $test_b_result->{'incubator_'.$temp.'_run1'} : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Inizio Incubazione</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="incubation_start_date_{{ $temp }}_run1" value="{{ old('incubation_start_date_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'}->format('Y-m-d') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    <input type="time" class="form-control" name="incubation_start_time_{{ $temp }}_run1" value="{{ old('incubation_start_time_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'}->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fine Incubazione</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="incubation_end_date_{{ $temp }}_run1" value="{{ old('incubation_end_date_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'}->format('Y-m-d') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    <input type="time" class="form-control" name="incubation_end_time_{{ $temp }}_run1" value="{{ old('incubation_end_time_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'}->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="temperature_{{ $temp }}_run1" class="form-label">Temperatura (°C)</label>
                                <input type="number" step="0.1" class="form-control" id="temperature_{{ $temp }}_run1" name="temperature_{{ $temp }}_run1" value="{{ old('temperature_'.$temp.'_run1', $is_edit ? $test_b_result->{'temperature_'.$temp.'_run1'} : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                        </div>

                        <table class="table table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Campione</th>
                                    <th>N. Acc. Piastra 1</th>
                                    <th>N. Acc. Piastra 2</th>
                                    <th colspan="2">Crescita Rilevata / Non Rilevata (P1)</th>
                                    <th colspan="2">Crescita Rilevata / Non Rilevata (P2)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['start' => 'Inizio Lotto', 'mid' => 'Metà Lotto', 'end' => 'Fine Lotto'] as $key => $label)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td>
                                        <input type="text" class="form-control" name="plate_id_{{ $key }}_plate1_{{ $temp }}_run1" value="{{ old('plate_id_'.$key.'_plate1_'.$temp.'_run1', $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate1_'.$temp.'_run1'} : $selected_plates_run1[$temp][$key.'_plate1']) }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="plate_id_{{ $key }}_plate2_{{ $temp }}_run1" value="{{ old('plate_id_'.$key.'_plate2_'.$temp.'_run1', $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate2_'.$temp.'_run1'} : $selected_plates_run1[$temp][$key.'_plate2']) }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    </td>
                                    @php
                                        $fieldName1 = 'growth_result_'.$temp.'_'.$key.'_plate1_run1';
                                        $currentValue1 = old($fieldName1, $is_edit ? $test_b_result->{$fieldName1} : '');
                                        $fieldName2 = 'growth_result_'.$temp.'_'.$key.'_plate2_run1';
                                        $currentValue2 = old($fieldName2, $is_edit ? $test_b_result->{$fieldName2} : '');
                                    @endphp
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_rilevata" value="rilevata" {{ $currentValue1 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Rilevata
                                    </td>
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_non_rilevata" value="non_rilevata" {{ $currentValue1 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Non Rilevata
                                    </td>
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_rilevata" value="rilevata" {{ $currentValue2 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Rilevata
                                    </td>
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_non_rilevata" value="non_rilevata" {{ $currentValue2 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Non Rilevata
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </fieldset>
                    @endforeach

                    @if($is_double_test_b)
                        <h5 class="mt-4">Secondo Run (Test in Doppio)</h5>
                        @foreach($incubation_types as $temp => $details)
                        <fieldset class="mb-4 p-3 border rounded {{ $details['color'] }}">
                            <legend class="h6 w-auto px-2">Incubazione a {{ $details['label'] }} (Run 2)</legend>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label for="incubator_{{ $temp }}_run2" class="form-label">Incubatore</label>
                                    <input type="text" class="form-control" id="incubator_{{ $temp }}_run2" name="incubator_{{ $temp }}_run2" value="{{ old('incubator_'.$temp.'_run2', $is_edit ? $test_b_result->{'incubator_'.$temp.'_run2'} : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Inizio Incubazione</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="incubation_start_date_{{ $temp }}_run2" value="{{ old('incubation_start_date_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'}->format('Y-m-d') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                        <input type="time" class="form-control" name="incubation_start_time_{{ $temp }}_run2" value="{{ old('incubation_start_time_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'}->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fine Incubazione</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="incubation_end_date_{{ $temp }}_run2" value="{{ old('incubation_end_date_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'}->format('Y-m-d') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                        <input type="time" class="form-control" name="incubation_end_time_{{ $temp }}_run2" value="{{ old('incubation_end_time_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'}->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="temperature_{{ $temp }}_run2" class="form-label">Temperatura (°C)</label>
                                    <input type="number" step="0.1" class="form-control" id="temperature_{{ $temp }}_run2" name="temperature_{{ $temp }}_run2" value="{{ old('temperature_'.$temp.'_run2', $is_edit ? $test_b_result->{'temperature_'.$temp.'_run2'} : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                                </div>
                            </div>

                            <table class="table table-bordered text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Campione</th>
                                        <th>N. Acc. Piastra 1</th>
                                        <th>N. Acc. Piastra 2</th>
                                        <th colspan="2">Crescita Rilevata / Non Rilevata (P1)</th>
                                        <th colspan="2">Crescita Rilevata / Non Rilevata (P2)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['start' => 'Inizio Lotto', 'mid' => 'Metà Lotto', 'end' => 'Fine Lotto'] as $key => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                        <td>
                                            <input type="text" class="form-control" name="plate_id_{{ $key }}_plate1_{{ $temp }}_run2" value="{{ old('plate_id_'.$key.'_plate1_'.$temp.'_run2', $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate1_'.$temp.'_run2'} : $selected_plates_run2[$temp][$key.'_plate1']) }}" {{ $is_readonly ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="plate_id_{{ $key }}_plate2_{{ $temp }}_run2" value="{{ old('plate_id_'.$key.'_plate2_'.$temp.'_run2', $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate2_'.$temp.'_run2'} : $selected_plates_run2[$temp][$key.'_plate2']) }}" {{ $is_readonly ? 'disabled' : '' }}>
                                        </td>
                                        @php
                                            $fieldName1 = 'growth_result_'.$temp.'_'.$key.'_plate1_run2';
                                            $currentValue1 = old($fieldName1, $is_edit ? $test_b_result->{$fieldName1} : '');
                                            $fieldName2 = 'growth_result_'.$temp.'_'.$key.'_plate2_run2';
                                            $currentValue2 = old($fieldName2, $is_edit ? $test_b_result->{$fieldName2} : '');
                                        @endphp
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_rilevata" value="rilevata" {{ $currentValue1 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Rilevata
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_non_rilevata" value="non_rilevata" {{ $currentValue1 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Non Rilevata
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_rilevata" value="rilevata" {{ $currentValue2 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Rilevata
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_non_rilevata" value="non_rilevata" {{ $currentValue2 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}> Non Rilevata
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </fieldset>
                        @endforeach
                    @endif

                    {{-- Esito --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Risultato di Conformità dello Stato Microbiologico</legend>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_idoneo" value="idoneo" {{ old('outcome', $is_edit ? $test_b_result->outcome : 'idoneo') == 'idoneo' ? 'checked' : '' }} required {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="outcome_idoneo">Idoneo</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_non_idoneo" value="non_idoneo" {{ old('outcome', $is_edit ? $test_b_result->outcome : '') == 'non_idoneo' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="outcome_non_idoneo">Non Idoneo</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" id="non-compliance-section" style="display: none;">
                            <label for="non_compliance_ref" class="form-label">Riferimento Non Conformità</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                                <input type="text" class="form-control" id="non_compliance_ref" name="non_compliance_ref" placeholder="Inserire riferimento NC" value="{{ old('non_compliance_ref', $is_edit ? $test_b_result->non_compliance_ref : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Note --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Note</legend>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Eventuali note aggiuntive" {{ $is_readonly ? 'disabled' : '' }}>{{ old('notes', $is_edit ? $test_b_result->notes : '') }}</textarea>
                    </fieldset>

                    {{-- Motivazione Modifica --}}
                    @if($is_edit && !$is_readonly)
                    <fieldset class="mb-4">
                        <legend class="h5">Motivazione della Modifica</legend>
                        <div class="form-group">
                            <label for="modification_reason" class="form-label">La modifica dei risultati richiede una motivazione (min. 10 caratteri).</label>
                            <textarea class="form-control @error('modification_reason') is-invalid @enderror" id="modification_reason" name="modification_reason" rows="3" required minlength="10">{{ old('modification_reason') }}</textarea>
                            @error('modification_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="invalid-feedback">Per favore, inserisci una motivazione per la modifica.</div>
                            @enderror
                        </div>
                    </fieldset>
                    @endif

                    {{-- Validazione --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Validazione</legend>
                        <div class="row g-3 p-3 bg-light border rounded">
                            @if($is_edit && $test_b_result->rl_signature_id)
                                @php
                                    $validatorName = $usersMap[$test_b_result->rl_signature_id]['operatore'] ?? 'N/D';
                                    $validationDate = $test_b_result->rl_signed_at ? \Carbon\Carbon::parse($test_b_result->rl_signed_at)->format('d/m/Y H:i') : '';
                                @endphp
                                <div class="col-md-6">
                                    <label class="form-label">Validato da RL</label>
                                    <input type="text" class="form-control" value="{{ $validatorName }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">in data</label>
                                    <input type="text" class="form-control" value="{{ $validationDate }}" disabled>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label">Validazione RL</label>
                                    <input type="text" class="form-control" placeholder="Validazione pendente" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">in data</label>
                                    <input type="text" class="form-control" placeholder="N/D" disabled>
                                </div>
                            @endif
                        </div>
                    </fieldset>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('acceptance.index') }}" class="btn btn-secondary btn-lg">
                            @if($is_readonly) <i class="fas fa-arrow-left me-2"></i>Torna all'elenco @else <i class="fas fa-times me-2"></i>Annulla @endif
                        </a>
                        @if(!$is_readonly)
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>{{ $is_edit ? 'Salva Modifiche' : 'Salva Risultati' }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer class="mt-auto text-center py-3 bg-white">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione validazione Bootstrap
            var form = document.querySelector('.needs-validation');
            if (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            }

            // Gestione visibilità campo Non Conformità
            const outcomeRadios = document.querySelectorAll('input[name="outcome"]');
            const nonComplianceSection = document.getElementById('non-compliance-section');
            const nonComplianceInput = document.getElementById('non_compliance_ref');

            function toggleNonCompliance() {
                const isNonIdoneo = document.getElementById('outcome_non_idoneo').checked;
                if (isNonIdoneo) {
                    nonComplianceSection.style.display = 'block';
                    nonComplianceInput.required = true;
                } else {
                    nonComplianceSection.style.display = 'none';
                    nonComplianceInput.required = false;
                    nonComplianceInput.value = '';
                }
            }

            outcomeRadios.forEach(radio => radio.addEventListener('change', toggleNonCompliance));
            toggleNonCompliance(); // Esegui al caricamento
        });
    </script>
</body>
</html>