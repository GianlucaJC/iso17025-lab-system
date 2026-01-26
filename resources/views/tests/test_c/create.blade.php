<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    @php
        $is_edit = isset($test_c_result);
        $is_readonly = $is_readonly ?? false;
    @endphp
    <title>{{ $is_edit ? 'Modifica / Visualizza' : 'Inserisci' }} Risultati Test C</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <div class="card-header">
                <h4>{{ $is_edit ? 'Modifica / Visualizza Risultati Test C' : 'Inserisci Risultati Test C' }}</h4>
                <p>Accettazione N°: <strong>{{ $acceptance->acceptance_number }}</strong> | Lotto: <strong>{{ $acceptance->lotto }}</strong></p>
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

                    <fieldset {{ $is_readonly ? 'disabled' : '' }}>
                        {{-- Dati Generali --}}
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="test_start_date" class="form-label">Data Inizio Prova</label>
                                <input type="date" class="form-control" id="test_start_date" name="test_start_date" value="{{ old('test_start_date', $is_edit ? $test_c_result->test_start_datetime->format('Y-m-d') : '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="test_start_time" class="form-label">Ora Inizio Prova</label>
                                <input type="time" class="form-control" id="test_start_time" name="test_start_time" value="{{ old('test_start_time', $is_edit ? $test_c_result->test_start_datetime->format('H:i') : '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="test_end_date" class="form-label">Data Fine Prova</label>
                                <input type="date" class="form-control" id="test_end_date" name="test_end_date" value="{{ old('test_end_date', $is_edit ? $test_c_result->test_end_datetime->format('Y-m-d') : '') }}" required>
                            </div>
                            <div class="col-md-3">
                                <label for="test_end_time" class="form-label">Ora Fine Prova</label>
                                <input type="time" class="form-control" id="test_end_time" name="test_end_time" value="{{ old('test_end_time', $is_edit ? $test_c_result->test_end_datetime->format('H:i') : '') }}" required>
                            </div>
                        </div>

                        {{-- Risultati Run 1 --}}
                        <h5 class="mt-4">Risultati Crescita - Run 1</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Piastra</th>
                                        <th>ID Piastra</th>
                                        <th>Risultato Crescita</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $run1_plates = [
                                            'plate_1' => 'Piastra 1',
                                            'plate_2' => 'Piastra 2',
                                            'plate_3' => 'Piastra 3',
                                            'control_blank' => 'Controllo Bianco',
                                            'control_tsa' => 'Controllo TSA',
                                        ];
                                    @endphp
                                    @foreach($run1_plates as $key => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                    <td class="align-middle">
                                        <span class="badge bg-secondary">{{ $selected_plates[$key] ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                        {{-- old() gestisce il valore precedente in caso di errore di validazione --}}
                                            <select class="form-select" name="growth_result_{{ $key }}" required>
                                                <option value="">Seleziona...</option>
                                                <option value="rilevata" {{ old('growth_result_'.$key, $is_edit ? $test_c_result->{'growth_result_'.$key} : '') == 'rilevata' ? 'selected' : '' }}>Rilevata</option>
                                                <option value="non_rilevata" {{ old('growth_result_'.$key, $is_edit ? $test_c_result->{'growth_result_'.$key} : '') == 'non_rilevata' ? 'selected' : '' }}>Non Rilevata</option>
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Risultati Run 2 (se doppio) --}}
                        @if($is_double_test_c)
                        <h5 class="mt-4">Risultati Crescita - Run 2 (Doppio)</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Piastra</th>
                                        <th>ID Piastra</th>
                                        <th>Risultato Crescita</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $run2_plates = [
                                            'plate_1_run2' => 'Piastra 1 (Run 2)',
                                            'plate_2_run2' => 'Piastra 2 (Run 2)',
                                            'plate_3_run2' => 'Piastra 3 (Run 2)',
                                            'control_blank_run2' => 'Controllo Bianco (Run 2)',
                                            'control_tsa_run2' => 'Controllo TSA (Run 2)',
                                        ];
                                    @endphp
                                    @foreach($run2_plates as $key => $label)
                                    <tr>
                                        <td>{{ $label }}</td>
                                    <td class="align-middle">
                                        <span class="badge bg-secondary">{{ $selected_plates_run2[str_replace('_run2', '', $key)] ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                        {{-- old() gestisce il valore precedente in caso di errore di validazione --}}
                                            <select class="form-select" name="growth_result_{{ $key }}" required>
                                                <option value="">Seleziona...</option>
                                                <option value="rilevata" {{ old('growth_result_'.$key, $is_edit ? $test_c_result->{'growth_result_'.$key} : '') == 'rilevata' ? 'selected' : '' }}>Rilevata</option>
                                                <option value="non_rilevata" {{ old('growth_result_'.$key, $is_edit ? $test_c_result->{'growth_result_'.$key} : '') == 'non_rilevata' ? 'selected' : '' }}>Non Rilevata</option>
                                            </select>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Esito e Note --}}
                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Esito Finale</label>
                                <div class="mt-2">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="outcome" id="outcome_idoneo" value="idoneo"
                                               {{ old('outcome', $is_edit ? $test_c_result->outcome : '') == 'idoneo' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="outcome_idoneo">Idoneo</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="outcome" id="outcome_non_idoneo" value="non_idoneo"
                                               {{ old('outcome', $is_edit ? $test_c_result->outcome : '') == 'non_idoneo' ? 'checked' : '' }} required>
                                        <label class="form-check-label" for="outcome_non_idoneo">Non Idoneo</label>
                                    </div>
                                </div>
                                <div class="invalid-feedback d-block" style="margin-top: .25rem;">Seleziona un esito.</div>
                            </div>
                            <div class="col-md-8">
                                <label for="non_compliance_ref" class="form-label">Riferimento Non Conformità (se applicabile)</label>
                            {{-- Il campo è required_if:outcome,non_idoneo tramite validazione del controller --}}
                            <input type="text" class="form-control" id="non_compliance_ref" name="non_compliance_ref" value="{{ old('non_compliance_ref', $is_edit ? $test_c_result->non_compliance_ref : '') }}" {{ old('outcome', $is_edit ? $test_c_result->outcome : '') == 'non_idoneo' ? 'required' : '' }}>
                            <div class="invalid-feedback">Il riferimento di non conformità è obbligatorio quando l'esito è "Non Idoneo".</div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="notes" class="form-label">Note</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $is_edit ? $test_c_result->notes : '') }}</textarea>
                            <div class="invalid-feedback">Inserisci almeno 10 caratteri per la motivazione.</div>
                            </div>
                        
                        </div>
                    </fieldset>

                    {{-- Motivo Modifica --}}
                    @if($is_edit && !$is_readonly)
                        <div class="row mt-4">
                            <div class="col-12">
                                <label for="modification_reason" class="form-label">Motivo della Modifica</label>
                                <textarea class="form-control" id="modification_reason" name="modification_reason" rows="2" required minlength="10">{{ old('modification_reason') }}</textarea>
                            </div>
                        </div>
                    @endif

                    {{-- Pulsanti Azione --}}
                    <div class="mt-4">
                        @if(!$is_readonly)
                            <button type="submit" class="btn btn-primary">{{ $is_edit ? 'Aggiorna Risultati' : 'Salva Risultati' }}</button>
                        @endif
                        <a href="{{ route('acceptance.index') }}" class="btn btn-secondary">Annulla</a>
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
                            @else
                                <p class="text-muted">Non ancora firmato</p>
                                @if(
                                    !$is_readonly &&
                                    !$test_c_result->lab_signed_at &&
                                    $currentUser['id'] === $test_c_result->operator_id &&
                                    isset($currentUser['user17025']) && $currentUser['user17025'] == 3
                                )
                                    <form action="{{ route('test-c.sign', $test_c_result->id) }}" method="POST" onsubmit="return confirm('Sei sicuro di voler firmare questo test? L\'azione non è reversibile.');">
                                        @csrf
                                        <button type="submit" class="btn btn-success">Apponi Firma</button>
                                    </form>
                                @endif
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
                                    <form action="{{ route('test-c.validate', $test_c_result->id) }}" method="POST" onsubmit="return confirm('Sei sicuro di voler validare questo test? L\'azione non è reversibile.');">
                                        @csrf
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
            const nonComplianceRefInput = document.getElementById('non_compliance_ref');
            const nonComplianceRefDiv = nonComplianceRefInput.closest('.col-md-8'); // O il div genitore che contiene label e input

            function toggleNonComplianceField() {
                const selectedOutcome = document.querySelector('input[name="outcome"]:checked');
                if (selectedOutcome && selectedOutcome.value === 'non_idoneo') {
                    nonComplianceRefInput.setAttribute('required', 'required');
                    nonComplianceRefDiv.style.display = 'block'; // Assicurati che sia visibile
                } else {
                    nonComplianceRefInput.removeAttribute('required');
                    nonComplianceRefInput.value = ''; // Pulisci il campo se non è più richiesto
                    nonComplianceRefInput.classList.remove('is-invalid'); // Rimuovi validazione se presente
                    nonComplianceRefDiv.style.display = 'none';
                }
            }

            // Inizializza lo stato del campo al caricamento della pagina
            toggleNonComplianceField();

            // Aggiungi l'event listener per il cambio di selezione su ogni radio button
            outcomeRadios.forEach(radio => {
                radio.addEventListener('change', toggleNonComplianceField);
            });

            // Gestione conferma firma con SweetAlert2
            $('form.sign-form').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                Swal.fire({
                    title: 'Sei sicuro di voler firmare questo test?',
                    text: "L'azione non è reversibile e bloccherà le modifiche.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sì, firma!',
                    cancelButtonText: 'Annulla'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Gestione conferma validazione con SweetAlert2
            $('form.validate-form').on('submit', function(event) {
                event.preventDefault();
                var form = this;
                Swal.fire({
                    title: 'Sei sicuro di voler validare questo test?',
                    text: "L'azione è definitiva e renderà il test immutabile.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
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