<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    @php
        $is_edit = isset($test_b_result);
        $is_readonly = $is_readonly ?? false;
        $is_initial_creation = $is_initial_creation ?? false;
        $is_completion_phase = ($is_edit && is_null($test_b_result->test_end_datetime)) ?? false;
        $form_title = $is_edit ? ($is_readonly ? 'Visualizza Risultati' : 'Modifica Risultati') : 'Esecuzione';
    @endphp
    <title>{{ $form_title }} Test B - Controllo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <div>
                @if(!($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4))
                    <a href="{{ route('acceptance.create') }}" class="btn btn-success"><i class="fas fa-plus-circle me-2"></i>Nuova Accettazione</a>
                @endif
                <button id="show-docs-btn" class="btn btn-link nav-link d-inline-block align-middle ms-2">
                    <i class="fas fa-book me-1"></i>Guida ISO 17025
                </button>
                <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
                @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                    <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('instruments.index') }}">Gestione Strumenti</a>
                @endif
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
                    Test B: Controllo della contaminazione microbica
                </h3>
            </div>
            <div class="card-body p-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <form method="POST" action="{{ $is_edit ? route('test-b.update', $test_b_result->id) : route('test-b.store', $acceptance->id) }}" class="needs-validation" novalidate>
                    @csrf
                    @if($is_edit) @method('PUT') @endif
                    <input type="hidden" name="edit_mode" id="edit_mode" value="">

                    {{-- Dati di Riferimento --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Dati di Riferimento</legend>
                        <div class="row p-3 bg-light border rounded">
                            <div class="col-md-4"><label class="form-label fw-bold">Lotto</label><p class="form-control-plaintext">{{ $acceptance->lotto }}</p></div>
                            <div class="col-md-4"><label class="form-label fw-bold">N. Accettazione</label><p class="form-control-plaintext">{{ $acceptance->acceptance_number }}</p></div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Operatore Accettazione</label>
                                @php
                                    $acceptanceOperatorName = $usersMap[$acceptance->user_id]['operatore'] ?? 'N/D';
                                @endphp
                                <p class="form-control-plaintext">{{ $acceptanceOperatorName }}</p></div>
                        </div>
                    </fieldset>

                    {{-- Dati Generali Prova --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Dati Generali Prova</legend>
                        <div class="row g-3">
                            <div class="col-md-6 initial-data-section">
                                <label class="form-label">Inizio Prova</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="test_start_date" value="{{ old('test_start_date', $is_edit ? $test_b_result->test_start_datetime->format('Y-m-d') : \Carbon\Carbon::parse($acceptance->acceptance_date)->format('Y-m-d')) }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                    <input type="time" class="form-control" name="test_start_time" value="{{ old('test_start_time', $is_edit ? $test_b_result->test_start_datetime->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                </div>
                            </div>
                            <div class="col-md-6 final-results-section">
                                <label class="form-label">Fine Prova</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="test_end_date" value="{{ old('test_end_date', ($is_edit && $test_b_result->test_end_datetime) ? $test_b_result->test_end_datetime->format('Y-m-d') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                    <input type="time" class="form-control" name="test_end_time" value="{{ old('test_end_time', ($is_edit && $test_b_result->test_end_datetime) ? $test_b_result->test_end_datetime->format('H:i') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
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
                        <div class="row g-3 mb-3 align-items-end">
                            <div class="col-md-3 initial-data-section">
                                <label for="incubator_{{ $temp }}_run1" class="form-label">Incubatore</label>
                                <select class="form-select @error('incubator_'.$temp.'_run1') is-invalid @enderror" id="incubator_{{ $temp }}_run1" name="incubator_{{ $temp }}_run1" {{ $is_readonly ? 'disabled' : '' }} required>
                                    <option value="">Seleziona incubatore...</option>
                                    @foreach($incubators as $incubator)
                                        @php
                                            $selectedValue = old('incubator_'.$temp.'_run1', $is_edit ? $test_b_result->{'incubator_'.$temp.'_run1'} : '');
                                        @endphp
                                        <option value="{{ $incubator->identifier }}" {{ $selectedValue == $incubator->identifier ? 'selected' : '' }}>
                                            {{ $incubator->identifier }} {{ $incubator->description ? '('.$incubator->description.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    @error('incubator_'.$temp.'_run1') {{ $message }} @else Selezionare un incubatore. @enderror
                                </div> 
                            </div>
                            <div class="col-md-3 initial-data-section">
                                <label class="form-label">Inizio Incubazione</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="incubation_start_date_{{ $temp }}_run1" value="{{ old('incubation_start_date_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'}->format('Y-m-d') : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                    <input type="time" class="form-control" name="incubation_start_time_{{ $temp }}_run1" value="{{ old('incubation_start_time_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run1'}->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                </div>
                            </div>
                            <div class="col-md-3 final-results-section">
                                <label class="form-label">Fine Incubazione</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" name="incubation_end_date_{{ $temp }}_run1" value="{{ old('incubation_end_date_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'}->format('Y-m-d') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                    <input type="time" class="form-control" name="incubation_end_time_{{ $temp }}_run1" value="{{ old('incubation_end_time_'.$temp.'_run1', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run1'}->format('H:i') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-3 initial-data-section">
                                <label for="temperature_{{ $temp }}_run1" class="form-label">Temperatura (°C)</label>
                                <input type="number" step="0.1" class="form-control @error('temperature_'.$temp.'_run1') is-invalid @enderror" id="temperature_{{ $temp }}_run1" name="temperature_{{ $temp }}_run1" value="{{ old('temperature_'.$temp.'_run1', $is_edit ? $test_b_result->{'temperature_'.$temp.'_run1'} : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                <div class="invalid-feedback">
                                    @error('temperature_'.$temp.'_run1') {{ $message }} @else Inserire la temperatura. @enderror
                                </div>
                            </div>
                        </div>

                        <div class="final-results-section"><table class="table table-bordered text-center">
                            <thead class="table-light">
                                <tr>
                                    <th class="d-none">Campione</th>
                                    <th>N. Acc. Piastra 1</th>
                                    <th>N. Acc. Piastra 2</th>
                                    <th colspan="2">Crescita Rilevata / Non Rilevata (P1)</th>
                                    <th colspan="2">Crescita Rilevata / Non Rilevata (P2)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(['start' => 'Inizio Lotto', 'mid' => 'Metà Lotto', 'end' => 'Fine Lotto'] as $key => $label)
                                <tr>
                                    <td class="d-none">{{ $label }}</td>
                                    <td>
                                        @php
                                            $plate_value1 = $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate1_'.$temp.'_run1'} : ($selected_plates_run1[$temp][$key.'_plate1'] ?? 'N/D');
                                        @endphp
                                        <span class="badge bg-dark fs-6">{{ $plate_value1 }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $plate_value2 = $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate2_'.$temp.'_run1'} : ($selected_plates_run1[$temp][$key.'_plate2'] ?? 'N/D');
                                        @endphp
                                        <span class="badge bg-dark fs-6">{{ $plate_value2 }}</span>
                                    </td>
                                    @php
                                        $fieldName1 = 'growth_result_'.$temp.'_'.$key.'_plate1_run1';
                                        $currentValue1 = old($fieldName1, $is_edit ? $test_b_result->{$fieldName1} : '');
                                        $fieldName2 = 'growth_result_'.$temp.'_'.$key.'_plate2_run1';
                                        $currentValue2 = old($fieldName2, $is_edit ? $test_b_result->{$fieldName2} : '');
                                    @endphp
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_rilevata" value="rilevata" {{ $currentValue1 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}> Rilevata
                                    </td>
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_non_rilevata" value="non_rilevata" {{ $currentValue1 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}> Non Rilevata
                                    </td>
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_rilevata" value="rilevata" {{ $currentValue2 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}> Rilevata
                                    </td>
                                    <td>
                                        <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_non_rilevata" value="non_rilevata" {{ $currentValue2 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}> Non Rilevata
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    </fieldset>
                    @endforeach

                    @if($is_double_test_b)
                        <h5 class="mt-4">Secondo Run (Test in Doppio)</h5>
                        @foreach($incubation_types as $temp => $details)
                        <fieldset class="mb-4 p-3 border rounded {{ $details['color'] }}">
                            <legend class="h6 w-auto px-2">Incubazione a {{ $details['label'] }} (Run 2)</legend>
                            <div class="row g-3 mb-3 align-items-end">
                                <div class="col-md-3 initial-data-section">
                                    <label for="incubator_{{ $temp }}_run2" class="form-label">Incubatore</label>
                                    <select class="form-select @error('incubator_'.$temp.'_run2') is-invalid @enderror" id="incubator_{{ $temp }}_run2" name="incubator_{{ $temp }}_run2" {{ $is_readonly ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation ? 'required' : '' }}>
                                        <option value="">Seleziona incubatore...</option>
                                        @foreach($incubators as $incubator)
                                            @php
                                                $selectedValue = old('incubator_'.$temp.'_run2', $is_edit ? $test_b_result->{'incubator_'.$temp.'_run2'} : '');
                                            @endphp
                                            <option value="{{ $incubator->identifier }}" {{ $selectedValue == $incubator->identifier ? 'selected' : '' }}>
                                                {{ $incubator->identifier }} {{ $incubator->description ? '('.$incubator->description.')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">
                                        @error('incubator_'.$temp.'_run2') {{ $message }} @else Selezionare un incubatore. @enderror
                                    </div>
                                </div>
                                <div class="col-md-3 initial-data-section">
                                    <label class="form-label">Inizio Incubazione</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="incubation_start_date_{{ $temp }}_run2" value="{{ old('incubation_start_date_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'}->format('Y-m-d') : '') }}" {{ $is_readonly ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation ? 'required' : '' }}>
                                        <input type="time" class="form-control" name="incubation_start_time_{{ $temp }}_run2" value="{{ old('incubation_start_time_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_start_datetime_'.$temp.'_run2'}->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation ? 'required' : '' }}>
                                    </div>
                                </div>
                                <div class="col-md-3 final-results-section">
                                    <label class="form-label">Fine Incubazione</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="incubation_end_date_{{ $temp }}_run2" value="{{ old('incubation_end_date_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'}->format('Y-m-d') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                        <input type="time" class="form-control" name="incubation_end_time_{{ $temp }}_run2" value="{{ old('incubation_end_time_'.$temp.'_run2', $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'} ? $test_b_result->{'incubation_end_datetime_'.$temp.'_run2'}->format('H:i') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                    </div>
                                </div>
                                <div class="col-md-3 initial-data-section">
                                    <label for="temperature_{{ $temp }}_run2" class="form-label">Temperatura (°C)</label>
                                    <input type="number" step="0.1" class="form-control @error('temperature_'.$temp.'_run2') is-invalid @enderror" id="temperature_{{ $temp }}_run2" name="temperature_{{ $temp }}_run2" value="{{ old('temperature_'.$temp.'_run2', $is_edit ? $test_b_result->{'temperature_'.$temp.'_run2'} : '') }}" {{ $is_readonly ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation ? 'required' : '' }}>
                                    <div class="invalid-feedback">
                                        @error('temperature_'.$temp.'_run2') {{ $message }} @else Inserire la temperatura. @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="final-results-section"><table class="table table-bordered text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th class="d-none">Campione</th>
                                        <th>N. Acc. Piastra 1</th>
                                        <th>N. Acc. Piastra 2</th>
                                        <th colspan="2">Crescita Rilevata / Non Rilevata (P1)</th>
                                        <th colspan="2">Crescita Rilevata / Non Rilevata (P2)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['start' => 'Inizio Lotto', 'mid' => 'Metà Lotto', 'end' => 'Fine Lotto'] as $key => $label)
                                    <tr>
                                        <td class="d-none">{{ $label }}</td>
                                        <td>
                                            @php
                                                $plate_value1_run2 = $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate1_'.$temp.'_run2'} : ($selected_plates_run2[$temp][$key.'_plate1'] ?? 'N/D');
                                            @endphp
                                            <span class="badge bg-dark fs-6">{{ $plate_value1_run2 }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $plate_value2_run2 = $is_edit ? $test_b_result->{'plate_id_'.$key.'_plate2_'.$temp.'_run2'} : ($selected_plates_run2[$temp][$key.'_plate2'] ?? 'N/D');
                                            @endphp
                                            <span class="badge bg-dark fs-6">{{ $plate_value2_run2 }}</span>
                                        </td>
                                        @php
                                            $fieldName1 = 'growth_result_'.$temp.'_'.$key.'_plate1_run2';
                                            $currentValue1 = old($fieldName1, $is_edit ? $test_b_result->{$fieldName1} : '');
                                            $fieldName2 = 'growth_result_'.$temp.'_'.$key.'_plate2_run2';
                                            $currentValue2 = old($fieldName2, $is_edit ? $test_b_result->{$fieldName2} : '');
                                        @endphp
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_rilevata" value="rilevata" {{ $currentValue1 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}> Rilevata
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName1 }}" id="{{ $fieldName1 }}_non_rilevata" value="non_rilevata" {{ $currentValue1 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}> Non Rilevata
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_rilevata" value="rilevata" {{ $currentValue2 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}> Rilevata
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="{{ $fieldName2 }}" id="{{ $fieldName2 }}_non_rilevata" value="non_rilevata" {{ $currentValue2 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}> Non Rilevata
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table></div>
                        </fieldset>
                        @endforeach
                    @endif

                    <div class="final-results-section">
                        {{-- Esito --}}
                        {{-- Esito Finale --}}
                        <div class="row mt-4 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Esito Finale</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="outcome" id="outcome_idoneo" value="idoneo" {{ old('outcome', $is_edit ? $test_b_result->outcome : '') == 'idoneo' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                        <label class="form-check-label" for="outcome_idoneo">Idoneo</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="outcome" id="outcome_non_idoneo" value="non_idoneo" {{ old('outcome', $is_edit ? $test_b_result->outcome : '') == 'non_idoneo' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                                        <label class="form-check-label" for="outcome_non_idoneo">Non Idoneo</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8" id="non-compliance-section" style="display: none;">
                                <label for="non_compliance_ref" class="form-label">Riferimento Non Conformità</label>
                                <input type="text" class="form-control" id="non_compliance_ref" name="non_compliance_ref" value="{{ old('non_compliance_ref', $is_edit ? $test_b_result->non_compliance_ref : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                                <div class="invalid-feedback">Il riferimento di non conformità è obbligatorio quando l'esito è "Non Idoneo".</div>
                            </div>
                        </div>

                        {{-- Note --}}
                        <fieldset class="mb-4">
                            <legend class="h5">Note</legend>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Eventuali note aggiuntive" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>{{ old('notes', $is_edit ? $test_b_result->notes : '') }}</textarea>
                        </fieldset>
                    </div>

                    {{-- Motivazione Modifica --}}
                    @if($is_edit && !$is_readonly)
                    <fieldset class="mb-4" id="modification-reason-section">
                        <legend class="h5">Motivazione della Modifica</legend>
                        <div class="form-group">
                            <label for="modification_reason" class="form-label">La motivazione è richiesta se si modificano i dati iniziali (prima del completamento della prova) o per qualsiasi modifica successiva al completamento. (min. 10 caratteri)</label>
                            <textarea class="form-control @error('modification_reason') is-invalid @enderror" id="modification_reason" name="modification_reason" rows="3" minlength="10">{{ old('modification_reason') }}</textarea>
                            @error('modification_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="invalid-feedback">Per favore, inserisci una motivazione valida per la modifica.</div>
                            @enderror
                        </div>
                    </fieldset>
                    @endif

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('acceptance.index') }}" class="btn btn-secondary btn-lg">
                            @if($is_readonly)
                                <i class="fas fa-arrow-left me-2"></i>Torna indietro
                            @else
                                <i class="fas fa-times me-2"></i>Annulla
                            @endif
                        </a>
                        @if(!$is_readonly)
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Salva Modifiche
                            </button>
                        @endif
                    </div>
                </form>

                {{-- Sezione Firme e Validazione (solo in visualizzazione/edit) --}}
                @if($is_edit)
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Operatore</h6>
                            <p>
                                @if(isset($usersMap[$test_b_result->operator_id]))
                                    {{ $usersMap[$test_b_result->operator_id]['operatore'] }}
                                @else
                                    ID: {{ $test_b_result->operator_id }} (Utente non trovato)
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6>Firma Tecnico di Laboratorio</h6>
                            @if($test_b_result->lab_signed_at)
                                <p class="text-success">
                                    Firmato da
                                    @if(isset($usersMap[$test_b_result->lab_signature_id]))
                                        <strong>{{ $usersMap[$test_b_result->lab_signature_id]['operatore'] }}</strong>
                                    @else
                                        <strong>ID: {{ $test_b_result->lab_signature_id }}</strong>
                                    @endif
                                    <br>
                                    il {{ $test_b_result->lab_signed_at->format('d/m/Y H:i') }}
                                </p>
                            @else
                                <p class="text-muted">Non ancora firmato</p>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <h6>Validazione Responsabile Laboratorio</h6>
                            @if($test_b_result->rl_signed_at)
                                <p class="text-primary">
                                    Validato da
                                    @if(isset($usersMap[$test_b_result->rl_signature_id]))
                                        <strong>{{ $usersMap[$test_b_result->rl_signature_id]['operatore'] }}</strong>
                                    @else
                                        <strong>ID: {{ $test_b_result->rl_signature_id }}</strong>
                                    @endif
                                    <br>
                                    il {{ $test_b_result->rl_signed_at->format('d/m/Y H:i') }}
                                </p>
                            @else
                                <p class="text-muted">Non ancora validato</p>
                                @if(
                                    $test_b_result->lab_signed_at &&
                                    !$test_b_result->rl_signed_at &&
                                    isset($currentUser['user17025']) && $currentUser['user17025'] == 4
                                )
                                    <form action="{{ route('test-b.validate', $test_b_result->id) }}" method="POST" class="validate-form">
                                        @csrf
                                        <input type="hidden" name="source" value="run_test">
                                        <button type="submit" class="btn btn-primary">Valida Test</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Link alla cronologia --}}
                    @if(isset($currentUser['user17025']) && $currentUser['user17025'] == 1)
                        <div class="mt-4">
                            <a href="{{ route('history.show', ['modelNameShort' => 'test-b-result', 'id' => $test_b_result->id]) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-history"></i> Vedi Cronologia Modifiche
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </main>

    <footer class="mt-auto text-center py-3 bg-white">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- Dipendenza per SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione validazione Bootstrap
            var form = document.querySelector('.needs-validation');
            if (form) {
                form.addEventListener('submit', function(event) {
                    // Re-enable all form elements just before submission to ensure their values are included.
                    // This is necessary because some fields might be disabled by the UI logic (e.g., phase 1 fields when editing phase 2).
                    form.querySelectorAll('input, select, textarea').forEach(el => {
                        el.disabled = false;
                    });

                    // Prima, controlla specificamente i radio button richiesti
                    const radioNames = [...new Set(
                        Array.from(form.querySelectorAll('input[type="radio"][required]')).map(radio => radio.name)
                    )];

                    let isRadioGroupInvalid = false;
                    for (const name of radioNames) {
                        if (!form.querySelector(`input[name="${name}"]:checked`)) {
                            isRadioGroupInvalid = true;
                            break;
                        }
                    }

                    // Controlla la validità generale del form
                    const isFormValid = form.checkValidity();

                    // Se il form non è valido, blocca l'invio
                    if (!isFormValid) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    // Se l'invalidità è dovuta a un radio button, mostra l'avviso Swal
                    if (isRadioGroupInvalid) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campi Obbligatori Mancanti',
                            text: 'Per favore, compila tutte le opzioni di crescita (Rilevata / Non Rilevata) prima di salvare.',
                            confirmButtonText: 'Ho capito'
                        });
                    }

                    // Aggiungi la classe per mostrare i feedback di validazione di Bootstrap
                    form.classList.add('was-validated');
                }, false);
            }
            // Gestione visibilità campo Non Conformità
            const outcomeRadios = document.querySelectorAll('input[name="outcome"]');
            const nonComplianceSection = document.getElementById('non-compliance-section');
            const nonComplianceInput = document.getElementById('non_compliance_ref');
            function toggleNonCompliance() {
                const isNonIdoneo = document.getElementById('outcome_non_idoneo').checked;
                const isDisabled = document.getElementById('outcome_non_idoneo').disabled;

                if (isNonIdoneo) {
                    nonComplianceSection.style.display = 'block';
                    if (!isDisabled) {
                        nonComplianceInput.required = true;
                    }
                } else {
                    nonComplianceSection.style.display = 'none';
                    nonComplianceInput.required = false;
                    // nonComplianceInput.value = ''; // Non pulire il valore, potrebbe essere utile se l'utente cambia idea
                }
            }
            outcomeRadios.forEach(radio => radio.addEventListener('change', toggleNonCompliance));
            toggleNonCompliance(); // Esegui al caricamento
            // Gestione conferma validazione con SweetAlert2
            $('form.validate-form').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                Swal.fire({
                    title: 'Sei sicuro di voler validare questo test?',
                    text: "L'azione è definitiva e renderà il test immutabile.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#0d6efd',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sì, valida!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Gestione modale documentazione
            const showDocsBtn = document.getElementById('show-docs-btn');
            if (showDocsBtn) {
                showDocsBtn.addEventListener('click', function() {
                    Swal.fire({
                        title: '<strong>Manuale Utente e Conformità ISO/IEC 17025:2017</strong>',
                        icon: 'info',
                        html: `
                        <div class="text-start">
                            <p class="text-center">Questo software è stato progettato per supportare le operazioni del laboratorio in conformità con i requisiti della norma <strong>ISO/IEC 17025:2017</strong>, garantendo la tracciabilità, l'integrità dei dati e il controllo degli accessi.</p>
                            <hr>
                            <h5 class="mt-3"><i class="fas fa-users-cog me-2 text-primary"></i>Gestione Ruoli e Permessi</h5>
                            <p>Il sistema si basa su ruoli definiti per garantire che solo il personale autorizzato possa eseguire determinate azioni, come richiesto dalla norma:</p>
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <span class="badge bg-primary">Tecnico di Laboratorio</span>: Responsabile della creazione delle accettazioni e dell'esecuzione/compilazione dei test. Può modificare solo i dati da lui inseriti, a patto che non siano stati ancora firmati. Appone la prima firma elettronica (<strong>Firma Tecnico</strong>).
                                </li>
                                <li class="list-group-item">
                                    <span class="badge bg-success">Responsabile Laboratorio</span>: Supervisiona i risultati. Non può creare o modificare dati operativi, ma ha il compito di validare i test già firmati dal tecnico, apponendo la seconda firma elettronica (<strong>Validazione RL</strong>).
                                </li>
                                <li class="list-group-item">
                                    <span class="badge bg-danger">Admin</span>: Ha visibilità completa su tutti i dati a scopo di audit e supervisione. Può visualizzare la cronologia delle modifiche ma non può alterare i dati di accettazione o i risultati dei test per preservare l'integrità del dato.
                                </li>
                            </ul>
                            <h5 class="mt-4"><i class="fas fa-project-diagram me-2 text-primary"></i>Flusso Operativo Controllato</h5>
                            <p>Il ciclo di vita di un test segue un percorso rigoroso per assicurare la corretta revisione e approvazione dei dati:</p>
                            <ol class="list-group list-group-numbered">
                                <li class="list-group-item"><strong>Accettazione</strong>: Il Tecnico crea una nuova scheda di accettazione.</li>
                                <li class="list-group-item"><strong>Esecuzione Test</strong>: Il Tecnico esegue un test e ne salva i risultati. Lo stato passa a <span class="badge bg-warning text-dark">In compilazione</span>.</li>
                                <li class="list-group-item"><strong>Firma del Tecnico</strong>: Una volta completato, il Tecnico firma il test. Questa azione blocca ogni modifica futura da parte sua. Lo stato passa a <span class="badge bg-primary">Firmato dal Tecnico</span>.</li>
                                <li class="list-group-item"><strong>Validazione RL</strong>: Il Responsabile Laboratorio revisiona il test firmato e, se corretto, lo valida. Questa è la seconda firma elettronica e rende il record <strong>immutabile</strong>. Lo stato passa a <span class="badge bg-success">Validato da RL</span>.</li>
                            </ol>
                            <h5 class="mt-4"><i class="fas fa-shield-alt me-2 text-primary"></i>Funzionalità Chiave per la Conformità</h5>
                            <dl>
                                <dt><i class="fas fa-history me-1"></i>Audit Trail (Cronologia)</dt>
                                <dd>
                                    Ogni modifica a una scheda di accettazione o a un risultato di test viene registrata. L'Admin può visualizzare la cronologia completa ( <button class="btn btn-secondary btn-sm py-0" disabled><i class="fas fa-history"></i></button> ) per ogni record, verificando <strong>chi</strong> ha modificato <strong>cosa</strong>, <strong>quando</strong> e <strong>perché</strong> (se richiesta una motivazione). Questo soddisfa il requisito di tracciabilità delle modifiche (punto 7.5 della norma).
                                </dd>
                                <dt class="mt-2"><i class="fas fa-signature me-1"></i>Firme Elettroniche e Integrità dei Dati</dt>
                                <dd>
                                    Il processo di firma a due livelli (Tecnico + RL) garantisce che i dati siano revisionati e approvati da personale autorizzato. Una volta validato, il record non è più modificabile, assicurando l'integrità e l'immodificabilità del dato finale come richiesto dalla norma (punto 7.11).
                                </dd>
                                <dt class="mt-2"><i class="fas fa-user-lock me-1"></i>Controllo degli Accessi</dt>
                                <dd>
                                    Il sistema limita le azioni in base al ruolo dell'utente loggato, impedendo a personale non autorizzato di eseguire operazioni critiche come la validazione dei risultati o la visualizzazione di dati sensibili come la cronologia (punto 6.2).
                                </dd>
                                <dt class="mt-2"><i class="fas fa-edit me-1"></i>Controllo delle Modifiche</dt>
                                <dd>
                                    Qualsiasi modifica a un record esistente (sia accettazione che risultati di test) richiede l'inserimento obbligatorio di una <strong>motivazione</strong>. Questa informazione viene salvata nell'Audit Trail, fornendo una giustificazione chiara per ogni cambiamento apportato ai dati.
                                </dd>
                            </dl>
                        </div>
                    `,
                        showCloseButton: true,
                        showCancelButton: false,
                        focusConfirm: false,
                        confirmButtonText: '<i class="fa fa-thumbs-up"></i> Ho capito!',
                        confirmButtonAriaLabel: 'Thumbs up, great!',
                        width: '80%',
                    });
                });
            }
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // The condition now shows the popup for any editable two-phase test.
        @if ($is_edit && !$is_readonly)
            
            const form = document.querySelector('form');
            form.style.visibility = 'hidden';
    
            // This variable is defined in the PHP block at the top of the file.
            // It's true if we are completing a test, false if we are modifying a completed one.
            const isCompletionPhase = {{ $is_completion_phase ? 'true' : 'false' }};
    
            const setupFormFor = (mode) => {
                const editModeInput = document.getElementById('edit_mode');
                const reasonSection = document.getElementById('modification-reason-section');
                const reasonTextarea = document.getElementById('modification_reason');
    
                const initialSections = document.querySelectorAll('.initial-data-section');
                const finalSections = document.querySelectorAll('.final-results-section');
    
                if (mode === 'initial') {
                    editModeInput.value = 'initial';
                    
                    initialSections.forEach(sec => sec.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false));
                    finalSections.forEach(sec => sec.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true));
    
                    // Reason is always required when editing existing phase 1 data.
                    if (reasonSection) reasonSection.style.display = 'block';
                    if (reasonTextarea) reasonTextarea.required = true;
    
                } else if (mode === 'final') {
                    editModeInput.value = 'final';
    
                    initialSections.forEach(sec => sec.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true));
                    finalSections.forEach(sec => sec.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false));
    
                    // Handle modification reason visibility and requirement based on completion status.
                    if (isCompletionPhase) {
                        // COMPLETING for the first time: no reason needed.
                        if (reasonSection) reasonSection.style.display = 'none';
                        if (reasonTextarea) reasonTextarea.required = false;
                    } else {
                        // MODIFYING an already completed test: reason is required.
                        if (reasonSection) reasonSection.style.display = 'block';
                        if (reasonTextarea) reasonTextarea.required = true;
                    }
                }
                form.style.visibility = 'visible';
            };
    
            Swal.fire({
                title: 'Cosa desideri fare?',
                text: "Scegli se modificare i dati iniziali (Fase 1) o compilare/modificare i risultati finali (Fase 2).",
                icon: 'question',
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-edit me-2"></i>Modifica Dati (Fase 1)',
                denyButtonText: '<i class="fas fa-clipboard-check me-2"></i>Compila Risultati (Fase 2)',
                allowOutsideClick: false,
                allowEscapeKey: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    // User chose "Modify Data (Phase 1)"
                    setupFormFor('initial');
                    Swal.fire('Modalità: Modifica Fase 1', 'I campi dei dati iniziali sono stati abilitati. La motivazione della modifica è obbligatoria.', 'warning');
                } else if (result.isDenied) {
                    // User chose "Compile/Modify Results (Phase 2)"
                    setupFormFor('final');
                    
                    const swalTitle = isCompletionPhase ? 'Modalità: Compilazione Fase 2' : 'Modalità: Modifica Fase 2';
                    const swalText = isCompletionPhase 
                        ? 'I campi relativi ai risultati finali sono stati abilitati.'
                        : 'I campi dei risultati finali sono stati abilitati. La motivazione della modifica è obbligatoria.';
                    const swalIcon = isCompletionPhase ? 'info' : 'warning';
                    Swal.fire(swalTitle, swalText, swalIcon);

                } else {
                    // User closed the popup, redirect them to the index page
                    window.location.href = "{{ route('acceptance.index') }}";
                }
            });
        @endif
    });
    </script>
</body>
</html>