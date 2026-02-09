<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    @php
        $is_edit = isset($test_c_result);
        $is_readonly = $is_readonly ?? false;
        $is_initial_creation = $is_initial_creation ?? false;
        $is_completion_phase = ($is_edit && is_null($test_c_result->test_end_datetime)) ?? false;
        $form_title = $is_edit ? ($is_readonly ? 'Visualizza Risultati' : 'Modifica Risultati') : 'Esecuzione';
    @endphp
    <title>{{ $form_title }} Test C - Controllo contaminazione microbica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <div>
                @if(!($currentUser['user17025'] == 1 || $currentUser['user17025'] == 4))
                    <a href="{{ route('acceptance.create') }}" class="btn btn-success">
                        <i class="fas fa-plus-circle me-2"></i>Nuova Accettazione
                    </a>
                @endif
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
        <div class="card">
            <div class="card-header {{ $is_edit && !$is_readonly ? 'bg-warning' : 'bg-primary text-white' }}">
                <h3>
                    @if($is_edit)
                        @if($is_readonly) <i class="fas fa-eye me-2"></i>Visualizza @else <i class="fas fa-edit me-2"></i>Modifica @endif
                    @else
                        <i class="fas fa-vial me-2"></i>Esecuzione
                    @endif
                    Test C: Controllo della contaminazione microbica
                </h3>
            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ $is_edit ? route('test-c.update', $test_c_result->id) : route('test-c.store', $acceptance->id) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    @if($is_edit)
                        @method('PUT')
                    @endif

                    {{-- Dati di Riferimento --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Dati di Riferimento</legend>
                        <div class="row p-3 bg-light border rounded">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Lotto</label>
                                <p class="form-control-plaintext">{{ $acceptance->lotto }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">N. Accettazione</label>
                                <p class="form-control-plaintext">{{ $acceptance->acceptance_number }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Operatore Accettazione</label>
                                @php $acceptanceOperatorName = $usersMap[$acceptance->user_id]['operatore'] ?? 'N/D'; @endphp
                                <p class="form-control-plaintext">{{ $acceptanceOperatorName }}</p>
                            </div>
                        </div>
                    </fieldset>
                    
                    <fieldset class="mb-4 p-3 border rounded">
                        <legend class="h5 w-auto px-2">Dati Generali Prova</legend>
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Inizio Prova</label>
                                <div class="input-group">
                                    <input type="time" class="form-control" name="test_start_time" value="{{ old('test_start_time', $is_edit ? $test_c_result->test_start_datetime->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                    <input type="date" class="form-control" name="test_start_date" value="{{ old('test_start_date', $is_edit ? $test_c_result->test_start_datetime->format('Y-m-d') : \Carbon\Carbon::parse($acceptance->acceptance_date)->format('Y-m-d')) }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fine Prova</label>
                                <div class="input-group">
                                    <input type="time" class="form-control" name="test_end_time" value="{{ old('test_end_time', ($is_edit && $test_c_result->test_end_datetime) ? $test_c_result->test_end_datetime->format('H:i') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                    <input type="date" class="form-control" name="test_end_date" value="{{ old('test_end_date', ($is_edit && $test_c_result->test_end_datetime) ? $test_c_result->test_end_datetime->format('Y-m-d') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Operatore</label>
                                <p class="form-control-plaintext">{{ $usersMap[$test_c_result->operator_id ?? $currentUser['id']]['operatore'] ?? 'N/D' }}</p>
                            </div>
                        </div>
                    </fieldset>

                    @php
                        $runs = ['' => 'Run 1'];
                        if ($is_double_test_c) {
                            $runs['_run2'] = 'Run 2 (Doppio)';
                        }
                    @endphp

                    @foreach($runs as $run_suffix => $run_label)
                        <fieldset class="mb-4 p-3 border rounded">
                            <legend class="h5 w-auto px-2">{{ $run_label }}</legend>

                            {{-- Preparazione --}}
                            <h6 class="mt-4">Preparazione e Diluizione</h6>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Piastra di TSA Sheep Blood (ID / Lotto)</label>
                                    @php
                                        $tsa_plate = ($run_suffix === '_run2' ? $selected_plates_run2['tsa_sheep_blood'] : $selected_plates['tsa_sheep_blood']);
                                    @endphp
                                    <p><span class="badge bg-secondary">{{ $tsa_plate['id'] ?? 'N/A' }}</span> / <span class="badge bg-info text-dark">{{ $tsa_plate['lot'] ?? 'N/A' }}</span></p>
                                </div>
                                <div class="col-md-3">
                                    <label for="pipette_dilution_1{{ $run_suffix }}" class="form-label">Pipetta Diluizione 1:10</label>
                                    <select class="form-select" id="pipette_dilution_1{{ $run_suffix }}" name="pipette_dilution_1{{ $run_suffix }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                        <option value="">Seleziona...</option>
                                        @foreach($pipettes as $pipette)
                                            <option value="{{ $pipette->identifier }}" {{ old('pipette_dilution_1'.$run_suffix, $is_edit ? $test_c_result->{'pipette_dilution_1'.$run_suffix} : '') == $pipette->identifier ? 'selected' : '' }}>{{ $pipette->identifier }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="pipette_dilution_2{{ $run_suffix }}" class="form-label">Pipetta Diluizione 1:100</label>
                                    <select class="form-select" id="pipette_dilution_2{{ $run_suffix }}" name="pipette_dilution_2{{ $run_suffix }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                        <option value="">Seleziona...</option>
                                        @foreach($pipettes as $pipette)
                                            <option value="{{ $pipette->identifier }}" {{ old('pipette_dilution_2'.$run_suffix, $is_edit ? $test_c_result->{'pipette_dilution_2'.$run_suffix} : '') == $pipette->identifier ? 'selected' : '' }}>{{ $pipette->identifier }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="pipette_inoculation{{ $run_suffix }}" class="form-label">Pipetta Inoculo 100 µl</label>
                                    <select class="form-select" id="pipette_inoculation{{ $run_suffix }}" name="pipette_inoculation{{ $run_suffix }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                        <option value="">Seleziona...</option>
                                        @foreach($pipettes as $pipette)
                                            <option value="{{ $pipette->identifier }}" {{ old('pipette_inoculation'.$run_suffix, $is_edit ? $test_c_result->{'pipette_inoculation'.$run_suffix} : '') == $pipette->identifier ? 'selected' : '' }}>{{ $pipette->identifier }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Incubazione --}}
                            <h6 class="mt-4">Incubazione Tryptic Soy Agar</h6>
                            <div class="row mb-3 align-items-end">
                                <div class="col-md-4">
                                    <label for="incubator{{ $run_suffix }}" class="form-label">Incubatore</label>
                                    <select class="form-select" id="incubator{{ $run_suffix }}" name="incubator{{ $run_suffix }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                        <option value="">Seleziona...</option>
                                        @foreach($incubators as $incubator)
                                            <option value="{{ $incubator->identifier }}" {{ old('incubator'.$run_suffix, $is_edit ? $test_c_result->{'incubator'.$run_suffix} : '') == $incubator->identifier ? 'selected' : '' }}>{{ $incubator->identifier }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Inizio Incubazione</label>
                                    <div class="input-group">
                                        <input type="time" class="form-control" name="incubation_start_time{{ $run_suffix }}" value="{{ old('incubation_start_time'.$run_suffix, $is_edit && $test_c_result->{'incubation_start_datetime'.$run_suffix} ? $test_c_result->{'incubation_start_datetime'.$run_suffix}->format('H:i') : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                        <input type="date" class="form-control" name="incubation_start_date{{ $run_suffix }}" value="{{ old('incubation_start_date'.$run_suffix, $is_edit && $test_c_result->{'incubation_start_datetime'.$run_suffix} ? $test_c_result->{'incubation_start_datetime'.$run_suffix}->format('Y-m-d') : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fine Incubazione</label>
                                    <div class="input-group">
                                        <input type="time" class="form-control" name="incubation_end_time{{ $run_suffix }}" value="{{ old('incubation_end_time'.$run_suffix, $is_edit && $test_c_result->{'incubation_end_datetime'.$run_suffix} ? $test_c_result->{'incubation_end_datetime'.$run_suffix}->format('H:i') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                        <input type="date" class="form-control" name="incubation_end_date{{ $run_suffix }}" value="{{ old('incubation_end_date'.$run_suffix, $is_edit && $test_c_result->{'incubation_end_datetime'.$run_suffix} ? $test_c_result->{'incubation_end_datetime'.$run_suffix}->format('Y-m-d') : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-end">
                                <div class="col-md-2">
                                    <label for="temperature{{ $run_suffix }}" class="form-label">Temp.(°C)</label>
                                    <input type="number" step="0.1" class="form-control" id="temperature{{ $run_suffix }}" name="temperature{{ $run_suffix }}" value="{{ old('temperature'.$run_suffix, $is_edit ? $test_c_result->{'temperature'.$run_suffix} : '') }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                </div>
                                <div class="col-md-2">
                                    <label for="tsa_growth_ufc{{ $run_suffix }}" class="form-label">Crescita UFC</label> {{-- Changed to text input with numeric filter --}}
                                    <input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control" id="tsa_growth_ufc{{ $run_suffix }}" name="tsa_growth_ufc{{ $run_suffix }}" value="{{ old('tsa_growth_ufc'.$run_suffix, $is_edit ? $test_c_result->{'tsa_growth_ufc'.$run_suffix} : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Crescita</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="tsa_growth_result{{ $run_suffix }}" id="tsa_growth_rilevata{{ $run_suffix }}" value="rilevata" {{ old('tsa_growth_result'.$run_suffix, $is_edit ? $test_c_result->{'tsa_growth_result'.$run_suffix} : '') == 'rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                            <label class="form-check-label" for="tsa_growth_rilevata{{ $run_suffix }}">Rilevata</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="tsa_growth_result{{ $run_suffix }}" id="tsa_growth_non_rilevata{{ $run_suffix }}" value="non_rilevata" {{ old('tsa_growth_result'.$run_suffix, $is_edit ? $test_c_result->{'tsa_growth_result'.$run_suffix} : '') == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="tsa_growth_non_rilevata{{ $run_suffix }}">Non Rilevata</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Risultati Inoculo --}}
                            <h6 class="mt-4">Risultati Inoculo 1000 - 10000 UFC</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center">
                                    <thead>
                                        <tr>
                                            <th class="d-none">Campione</th> {{-- Hidden as requested --}}
                                            <th>ID Piastra / Lotto</th>
                                            <th>UFC</th>
                                            <th>UFC &ge;50% UFC su TSA</th>
                                            <th>Crescita Rilevata</th>
                                            <th>Crescita Non Rilevata</th>                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $samples = [
                                                'start_lotto' => 'Inizio Lotto',
                                                'mid_lotto' => 'Metà Lotto',
                                                'end_lotto' => 'Fine Lotto',
                                            ];
                                            $current_plates = ($run_suffix === '_run2') ? $selected_plates_run2 : $selected_plates;
                                        @endphp
                                        @foreach($samples as $key => $label)
                                            <tr class="align-middle">
                                                <td class="d-none">{{ $label }}</td> {{-- Hidden as requested --}}
                                                <td><span class="badge bg-secondary">{{ $current_plates[$key]['id'] ?? 'N/A' }}</span></td> {{-- Only ID, no lot for these plates --}}
                                                @php
                                                    $fieldName = 'growth_result_'.$key.$run_suffix;
                                                    $currentValue = old($fieldName, $is_edit ? $test_c_result->{$fieldName} : '');
                                                    $ufcFieldName = 'ufc_'.$key.$run_suffix;
                                                    $ufc50PercentTsaFieldName = 'ufc_50_percent_tsa_'.$key.$run_suffix;
                                                @endphp
                                                <td><input type="text" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="form-control form-control-sm" name="{{ $ufcFieldName }}" value="{{ old($ufcFieldName, $is_edit ? $test_c_result->{$ufcFieldName} : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}></td>
                                                <td>
                                                    <input class="form-check-input" type="checkbox" name="{{ $ufc50PercentTsaFieldName }}" value="1" {{ old($ufc50PercentTsaFieldName, $is_edit ? $test_c_result->{$ufc50PercentTsaFieldName} : '') ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                                                </td>
                                                <td><input class="form-check-input" type="radio" name="{{ $fieldName }}" value="rilevata" {{ $currentValue == 'rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}></td>
                                                <td><input class="form-check-input" type="radio" name="{{ $fieldName }}" value="non_rilevata" {{ $currentValue == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Bianco di Controllo --}}
                            <h6 class="mt-4">Bianco di Controllo</h6>
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <label class="form-label">ID Piastra / Lotto</label>
                                    @php
                                        $control_blank_plate = $current_plates['control_blank'];
                                    @endphp
                                    <p><span class="badge bg-secondary">{{ $control_blank_plate['id'] ?? 'N/A' }}</span></p> {{-- Only ID, no lot for control blank --}}
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Crescita</label>
                                    @php
                                        $fieldName = 'growth_result_control_blank'.$run_suffix;
                                        $currentValue = old($fieldName, $is_edit ? $test_c_result->{$fieldName} : '');
                                    @endphp
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="{{ $fieldName }}" id="{{ $fieldName }}_rilevata" value="rilevata" {{ $currentValue == 'rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                            <label class="form-check-label" for="{{ $fieldName }}_rilevata">Rilevata</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="{{ $fieldName }}" id="{{ $fieldName }}_non_rilevata" value="non_rilevata" {{ $currentValue == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                                            <label class="form-check-label" for="{{ $fieldName }}_non_rilevata">Non Rilevata</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    @endforeach

                    {{-- Esito --}}
                    <fieldset class="mb-4">
                        <legend class="h5">Risultato di Conformità dello Stato Microbiologico</legend>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_idoneo" value="idoneo" {{ old('outcome', $is_edit ? $test_c_result->outcome : '') == 'idoneo' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }} {{ !$is_readonly && !$is_initial_creation && !$is_completion_phase ? 'required' : '' }}>
                                    <label class="form-check-label" for="outcome_idoneo">Idoneo</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_non_idoneo" value="non_idoneo" {{ old('outcome', $is_edit ? $test_c_result->outcome : '') == 'non_idoneo' ? 'checked' : '' }} {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                                    <label class="form-check-label" for="outcome_non_idoneo">Non Idoneo</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" id="non-compliance-section" style="display: none;">
                            <label for="non_compliance_ref" class="form-label">Riferimento Non Conformità</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                                <input type="text" class="form-control" id="non_compliance_ref" name="non_compliance_ref" placeholder="Inserire riferimento NC" value="{{ old('non_compliance_ref', $is_edit ? $test_c_result->non_compliance_ref : '') }}" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="row mt-4">
                            <div class="col-12">
                                <label for="notes" class="form-label">Note</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" {{ $is_readonly || $is_initial_creation ? 'disabled' : '' }}>{{ old('notes', $is_edit ? $test_c_result->notes : '') }}</textarea>
                            </div>
                        </div>

                    {{-- Motivo Modifica --}}
                    @if($is_edit && !$is_readonly)
                        <div class="row mt-4">
                            <div class="col-12">
                                <label for="modification_reason" class="form-label">Motivo della Modifica</label>
                                <textarea class="form-control" id="modification_reason" name="modification_reason" rows="2" minlength="10">{{ old('modification_reason') }}</textarea>
                            </div>
                        </div>
                    @endif

                    {{-- Pulsanti Azione --}}
                    <div class="mt-4">
                        @if(!$is_readonly)
                            <button type="submit" class="btn btn-primary">{{ $is_edit ? 'Aggiorna Risultati' : 'Salva Risultati' }}</button>
                        @endif
                        <a href="{{ route('acceptance.index') }}" class="btn btn-secondary">Torna indietro</a>
                    </div>
                </form>

                {{-- Sezione Firme e Validazione (solo in visualizzazione) --}}
                @if($is_edit)
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Operatore</h6>
                            <p>
                                @if(isset($usersMap[$test_c_result->operator_id]))
                                    {{ $usersMap[$test_c_result->operator_id]['operatore'] }}
                                @else
                                    ID: {{ $test_c_result->operator_id }} (Utente non trovato)
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6>Firma Tecnico di Laboratorio</h6>
                            @if($test_c_result->lab_signed_at)
                                <p class="text-success">
                                    Firmato da
                                    @if(isset($usersMap[$test_c_result->lab_signature_id]))
                                        <strong>{{ $usersMap[$test_c_result->lab_signature_id]['operatore'] }}</strong>
                                    @else
                                        <strong>ID: {{ $test_c_result->lab_signature_id }}</strong>
                                    @endif
                                    <br>
                                    il {{ $test_c_result->lab_signed_at->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <h6>Validazione Responsabile Laboratorio</h6>
                            @if($test_c_result->rl_signed_at)
                                <p class="text-primary">
                                    Validato da
                                    @if(isset($usersMap[$test_c_result->rl_signature_id]))
                                        <strong>{{ $usersMap[$test_c_result->rl_signature_id]['operatore'] }}</strong>
                                    @else
                                        <strong>ID: {{ $test_c_result->rl_signature_id }}</strong>
                                    @endif
                                    <br>
                                    il {{ $test_c_result->rl_signed_at->format('d/m/Y H:i') }}
                                </p>
                            @else
                                <p class="text-muted">Non ancora validato</p>
                                @if(
                                    $test_c_result->lab_signed_at &&
                                    !$test_c_result->rl_signed_at &&
                                    isset($currentUser['user17025']) && $currentUser['user17025'] == 4
                                )
                                    <form action="{{ route('test-c.validate', $test_c_result->id) }}" method="POST" class="validate-form">
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
                            <a href="{{ route('history.show', ['modelNameShort' => 'test-c-result', 'id' => $test_c_result->id]) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-history"></i> Vedi Cronologia Modifiche
                            </a>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </main>

    <footer class="mt-auto text-center py-3 bg-light">
        <small class="text-muted">&copy; Liofilchem srl - Software by Custom Software</small>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const outcomeRadios = document.querySelectorAll('input[name="outcome"]');
            const nonComplianceSection = document.getElementById('non-compliance-section');
            const nonComplianceInput = document.getElementById('non_compliance_ref');

            function toggleNonComplianceField() {
                const isNonIdoneo = document.getElementById('outcome_non_idoneo').checked;
                if (isNonIdoneo) {
                    nonComplianceSection.style.display = 'block';
                    nonComplianceInput.required = true;
                } else {
                    nonComplianceSection.style.display = 'none';
                    nonComplianceInput.required = false;
                    nonComplianceInput.value = ''; // Pulisci il campo
                    nonComplianceInput.classList.remove('is-invalid');
                }
            }

            // Inizializza lo stato del campo al caricamento della pagina
            toggleNonComplianceField();

            // Aggiungi l'event listener per il cambio di selezione
            outcomeRadios.forEach(radio => radio.addEventListener('change', toggleNonComplianceField));

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
        });
    </script>
</body>
</html>