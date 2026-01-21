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
                        $roleMap = [1 => 'Amministratore', 2 => 'Resp. Accettazione', 3 => 'Tecnico di Laboratorio', 4 => 'Resp. Qualità'];
                        $badgeColorMap = [1 => 'bg-danger', 2 => 'bg-info text-dark', 3 => 'bg-primary', 4 => 'bg-success'];
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
                <form method="POST" action="{{ $is_edit ? route('test-b.update', $test_b_result->id) : route('test-b.store', $acceptance->id) }}" class="needs-validation" novalidate data-is-double-test-b="{{ $is_double_test_b ? '1' : '0' }}" data-is-readonly="{{ $is_readonly ? '1' : '0' }}">
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
                                <div class="input-group has-validation">
                                    <input type="date" class="form-control" name="test_start_date" value="{{ old('test_start_date', $is_edit ? $test_b_result->test_start_datetime->format('Y-m-d') : date('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Data inizio prova obbligatoria.</div>
                                    <input type="time" class="form-control" name="test_start_time" value="{{ old('test_start_time', $is_edit ? $test_b_result->test_start_datetime->format('H:i') : date('H:i')) }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Ora inizio prova obbligatoria.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fine Prova</label>
                                <div class="input-group has-validation">
                                    <input type="date" class="form-control" name="test_end_date" value="{{ old('test_end_date', $is_edit ? $test_b_result->test_end_datetime->format('Y-m-d') : date('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Data fine prova obbligatoria.</div>
                                    <input type="time" class="form-control" name="test_end_time" value="{{ old('test_end_time', $is_edit ? $test_b_result->test_end_datetime->format('H:i') : date('H:i')) }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Ora fine prova obbligatoria.</div>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    @php $runs = $is_double_test_b ? [1, 2] : [1]; @endphp

                    @foreach($runs as $run_index)
                        @php
                            $run_suffix = $run_index > 1 ? '_run' . $run_index : '_run1';
                            $run_label = $run_index > 1 ? ' (Run ' . $run_index . ')' : '';
                            $available_plates = $run_index === 1 ? $available_plates_run1 : $available_plates_run2;
                            $selected_plates = $run_index === 1 ? $selected_plates_run1 : $selected_plates_run2;
                        @endphp

                        <h4 class="mt-4 mb-3">Dati Incubazione{{ $run_label }}</h4>

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
                                <div class="col-md-3 has-validation">
                                    <label for="incubator_{{ $temp }}{{ $run_suffix }}" class="form-label">Incubatore</label>
                                    <input type="text" class="form-control" id="incubator_{{ $temp }}{{ $run_suffix }}" name="incubator_{{ $temp }}{{ $run_suffix }}" value="{{ old('incubator_'.$temp.$run_suffix, $is_edit ? $test_b_result->{'incubator_'.$temp.$run_suffix} : '') }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Incubatore obbligatorio.</div>
                                </div>
                                <div class="col-md-3 has-validation">
                                    <label class="form-label">Inizio Incubazione</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="incubation_start_date_{{ $temp }}{{ $run_suffix }}" value="{{ old('incubation_start_date_'.$temp.$run_suffix, $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.$run_suffix} ? $test_b_result->{'incubation_start_datetime_'.$temp.$run_suffix}->format('Y-m-d') : '') }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Data inizio incubazione obbligatoria.</div>
                                        <input type="time" class="form-control" name="incubation_start_time_{{ $temp }}{{ $run_suffix }}" value="{{ old('incubation_start_time_'.$temp.$run_suffix, $is_edit && $test_b_result->{'incubation_start_datetime_'.$temp.$run_suffix} ? $test_b_result->{'incubation_start_datetime_'.$temp.$run_suffix}->format('H:i') : '') }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Ora inizio incubazione obbligatoria.</div>
                                    </div>
                                </div>
                                <div class="col-md-3 has-validation">
                                    <label class="form-label">Fine Incubazione</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" name="incubation_end_date_{{ $temp }}{{ $run_suffix }}" value="{{ old('incubation_end_date_'.$temp.$run_suffix, $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.$run_suffix} ? $test_b_result->{'incubation_end_datetime_'.$temp.$run_suffix}->format('Y-m-d') : '') }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Data fine incubazione obbligatoria.</div>
                                        <input type="time" class="form-control" name="incubation_end_time_{{ $temp }}{{ $run_suffix }}" value="{{ old('incubation_end_time_'.$temp.$run_suffix, $is_edit && $test_b_result->{'incubation_end_datetime_'.$temp.$run_suffix} ? $test_b_result->{'incubation_end_datetime_'.$temp.$run_suffix}->format('H:i') : '') }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Ora fine incubazione obbligatoria.</div>
                                    </div>
                                </div>
                                <div class="col-md-3 has-validation">
                                    <label for="temperature_{{ $temp }}{{ $run_suffix }}" class="form-label">Temperatura (°C)</label>
                                    <input type="number" step="0.1" class="form-control" id="temperature_{{ $temp }}{{ $run_suffix }}" name="temperature_{{ $temp }}{{ $run_suffix }}" value="{{ old('temperature_'.$temp.$run_suffix, $is_edit ? $test_b_result->{'temperature_'.$temp.$run_suffix} : '') }}" required {{ $is_readonly ? 'disabled' : '' }}><div class="invalid-feedback">Temperatura obbligatoria.</div>
                                </div>
                            </div>

                            <table class="table table-bordered text-center align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th rowspan="2" style="vertical-align: middle; display: none;">Campione</th> {{-- Colonna 1 --}}
                                        <th colspan="2" style="vertical-align: middle;">N. Acc. Piastra</th> {{-- Colonne 2-3 --}}
                                        <th colspan="2" style="vertical-align: middle;">Crescita Rilevata</th>
                                        <th colspan="2" style="vertical-align: middle;">Crescita Non Rilevata</th>
                                    </tr>
                                    <tr>
                                        <th>Piastra 1</th>
                                        <th>Piastra 2</th>
                                        <th>P1</th>
                                        <th>P2</th>
                                        <th>P1</th>
                                        <th>P2</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['start' => 'Inizio Lotto', 'mid' => 'Metà Lotto', 'end' => 'Fine Lotto'] as $key => $label)
                                    <tr>
                                        <td style="display: none;">
                                            {{ $label }}
                                            @php
                                                $fieldNameP1 = 'growth_result_'.$temp.'_'.$key.'_plate1'.$run_suffix;
                                                $currentValueP1 = old($fieldNameP1, $is_edit ? $test_b_result->{$fieldNameP1} : '');
                                                $fieldNameP2 = 'growth_result_'.$temp.'_'.$key.'_plate2'.$run_suffix;
                                                $currentValueP2 = old($fieldNameP2, $is_edit ? $test_b_result->{$fieldNameP2} : '');
                                            @endphp
                                            <input type="hidden" name="{{ $fieldNameP1 }}" value="{{ $currentValueP1 }}" {{ $is_readonly ? 'disabled' : '' }}>
                                            <input type="hidden" name="{{ $fieldNameP2 }}" value="{{ $currentValueP2 }}" {{ $is_readonly ? 'disabled' : '' }}>
                                        </td>
                                        {{-- Piastra 1 --}}
                                        <td>
                                            @php $plateId1 = old('plate_id_'.$key.'_plate1_'.$temp.$run_suffix, $selected_plates[$temp][$key.'_plate1'] ?? ''); @endphp
                                            <span class="badge bg-secondary">{{ $plateId1 }}</span>
                                            <input type="hidden" name="plate_id_{{ $key }}_plate1_{{ $temp }}{{ $run_suffix }}" value="{{ $plateId1 }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                        </td>
                                        {{-- Piastra 2 --}}
                                        <td>
                                            @php $plateId2 = old('plate_id_'.$key.'_plate2_'.$temp.$run_suffix, $selected_plates[$temp][$key.'_plate2'] ?? ''); @endphp
                                            <span class="badge bg-secondary">{{ $plateId2 }}</span>
                                            <input type="hidden" name="plate_id_{{ $key }}_plate2_{{ $temp }}{{ $run_suffix }}" value="{{ $plateId2 }}" {{ $is_readonly ? 'disabled' : '' }} required>
                                        </td>
                                        {{-- Crescita Rilevata P1 --}}
                                        <td>
                                            <input class="form-check-input growth-checkbox" type="checkbox" id="{{ $fieldNameP1 }}_rilevata" data-target-input="[name='{{ $fieldNameP1 }}']" data-value="rilevata" data-group="{{ $fieldNameP1 }}" {{ $currentValueP1 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                        </td>
                                        {{-- Crescita Rilevata P2 --}}
                                        <td>
                                            <input class="form-check-input growth-checkbox" type="checkbox" id="{{ $fieldNameP2 }}_rilevata" data-target-input="[name='{{ $fieldNameP2 }}']" data-value="rilevata" data-group="{{ $fieldNameP2 }}" {{ $currentValueP2 == 'rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                        </td>
                                        {{-- Crescita Non Rilevata P1 --}}
                                        <td>
                                            <input class="form-check-input growth-checkbox" type="checkbox" id="{{ $fieldNameP1 }}_non_rilevata" data-target-input="[name='{{ $fieldNameP1 }}']" data-value="non_rilevata" data-group="{{ $fieldNameP1 }}" {{ $currentValueP1 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                        </td>
                                        {{-- Crescita Non Rilevata P2 --}}
                                        <td>
                                            <input class="form-check-input growth-checkbox" type="checkbox" id="{{ $fieldNameP2 }}_non_rilevata" data-target-input="[name='{{ $fieldNameP2 }}']" data-value="non_rilevata" data-group="{{ $fieldNameP2 }}" {{ $currentValueP2 == 'non_rilevata' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </fieldset>
                        @endforeach
                    @endforeach

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
                             <div class="col-md-6">
                                <label class="form-label">Validato da RL</label>
                                <input type="text" class="form-control" placeholder="Validazione pendente" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">in data</label>
                                <input type="date" class="form-control" disabled>
                            </div>
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
                const isReadonly = form.dataset.isReadonly === '1';

                // Se il form è in sola lettura, non abilitare la validazione o gli eventi interattivi
                if (isReadonly) {
                    return;
                }

                form.addEventListener('submit', function (event) {
                    // --- 1. Esegui la validazione nativa di Bootstrap ---
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                        form.classList.add('was-validated');
                        return; // Esce se la validazione base fallisce
                    }
                    
                    // --- 2. Validazione personalizzata: TUTTI i risultati di crescita devono essere compilati ---
                    let customValidationFailed = false;
                    let firstErrorText = '';

                    const isDoubleTestB = form.dataset.isDoubleTestB === '1';
                    const runs = isDoubleTestB ? [1, 2] : [1];
                    const temps = ['35', '22'];
                    const positions = ['start', 'mid', 'end'];

                    for (const run of runs) {
                        if (customValidationFailed) break;
                        const runSuffix = run === 1 ? '_run1' : '_run' + run;
                        for (const temp of temps) {
                            if (customValidationFailed) break;
                            for (const position of positions) {
                                if (customValidationFailed) break;
                                const fieldNameP1 = `growth_result_${temp}_${position}_plate1${runSuffix}`;
                                const fieldNameP2 = `growth_result_${temp}_${position}_plate2${runSuffix}`;

                                const inputP1 = document.querySelector(`input[name="${fieldNameP1}"]`);
                                const inputP2 = document.querySelector(`input[name="${fieldNameP2}"]`);

                                if (inputP1 && !inputP1.value) {
                                    firstErrorText = `Il risultato di crescita per la Piastra 1 (Incubazione ${temp}°C, ${position.replace('start', 'Inizio Lotto').replace('mid', 'Metà Lotto').replace('end', 'Fine Lotto')}, Run ${run}) è obbligatorio.`;
                                    customValidationFailed = true;
                                } else if (inputP2 && !inputP2.value) {
                                    firstErrorText = `Il risultato di crescita per la Piastra 2 (Incubazione ${temp}°C, ${position.replace('start', 'Inizio Lotto').replace('mid', 'Metà Lotto').replace('end', 'Fine Lotto')}, Run ${run}) è obbligatorio.`;
                                    customValidationFailed = true;
                                }
                            }
                        }
                    }

                    // Se la validazione personalizzata è fallita, blocca il submit e mostra l'alert
                    if (customValidationFailed) {
                        event.preventDefault();
                        event.stopPropagation();
                        Swal.fire({
                            icon: 'error',
                            title: 'Compilazione Obbligatoria',
                            text: firstErrorText,
                        });
                        return; // Blocca il submit
                    }

                    // Se tutte le validazioni sono passate, aggiungi la classe per i feedback visivi di Bootstrap
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

            // Gestione checkbox di crescita per simulare radio button e popolare campi hidden
            const growthCheckboxes = document.querySelectorAll('.growth-checkbox');
            growthCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const groupName = this.dataset.group;
                    const targetInput = document.querySelector(this.dataset.targetInput);

                    // Se questo checkbox viene selezionato
                    if (this.checked) {
                        // Deseleziona gli altri checkbox nello stesso gruppo
                        const otherCheckboxesInGroup = document.querySelectorAll(`.growth-checkbox[data-group="${groupName}"]`);
                        otherCheckboxesInGroup.forEach(otherCheckbox => {
                            if (otherCheckbox !== this) {
                                otherCheckbox.checked = false;
                            }
                        });
                        // Imposta il valore del campo hidden
                        if (targetInput) targetInput.value = this.dataset.value;
                    } else {
                        // Se il checkbox viene deselezionato, svuota il campo hidden
                        if (targetInput) targetInput.value = '';
                    }
                });
            });
            outcomeRadios.forEach(radio => radio.addEventListener('change', toggleNonCompliance));
            toggleNonCompliance(); // Esegui al caricamento

        });
    </script>
</body>
</html>
