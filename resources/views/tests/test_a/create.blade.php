<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    @php
        $is_edit = isset($test_a_result);
        $is_readonly = $is_readonly ?? false;
        $form_title = $is_edit ? ($is_readonly ? 'Visualizza Risultati' : 'Modifica Risultati') : 'Esecuzione';
    @endphp
    <title>{{ $form_title }} Test A - Controllo pH</title>
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
    <style>
        .ph-slider-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .ph-slider-container input[type=range] {
            flex-grow: 1;
        }
        .ph-value-input {
            font-weight: bold;
            font-size: 1.2rem;
            text-align: center;
            width: 90px;
            flex-shrink: 0;
        }
        .outcome-radio .form-check-input {
            display: none;
        }
        .outcome-radio .form-check-label {
            border: 2px solid #dee2e6;
            border-radius: .375rem;
            padding: 1rem;
            cursor: pointer;
            text-align: center;
            width: 100%;
            transition: all 0.2s ease-in-out;
        }
        .outcome-radio .form-check-input:checked + .form-check-label {
            background-color: var(--bs-primary-bg-subtle);
            border-color: var(--bs-primary);
            color: var(--bs-primary-text-emphasis);
        }
        .outcome-radio .form-check-input:checked + .form-check-label.text-success {
             background-color: var(--bs-success-bg-subtle);
             border-color: var(--bs-success);
             color: var(--bs-success-text-emphasis);
        }
        .outcome-radio .form-check-input:checked + .form-check-label.text-danger {
             background-color: var(--bs-danger-bg-subtle);
             border-color: var(--bs-danger);
             color: var(--bs-danger-text-emphasis);
        }
        .outcome-radio .form-check-label .icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
    <!-- Navbar (copiata da altre viste per coerenza) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <div>
                <a href="{{ route('acceptance.create') }}" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>Nuova Accettazione
                </a>
                <a class="nav-link d-inline-block align-middle ms-3" href="{{ route('acceptance.index') }}">Elenco Accettazioni</a>
            </div>
            <div class="d-flex align-items-center">
                @if(Session::has('user'))
                    @php
                        $user = Session::get('user');
                        $roleId = $user['user17025'] ?? null;
                        $roleMap = [1 => 'Admin', 3 => 'Tecnico di Laboratorio', 4 => 'Responsabile Laboratorio']; // Updated role map
                        $badgeColorMap = [1 => 'bg-danger', 3 => 'bg-primary', 4 => 'bg-success']; // Updated badge color map
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
                        @if($is_readonly)
                            <i class="fas fa-eye me-2"></i>Visualizza Risultati Test A
                        @else
                            <i class="fas fa-edit me-2"></i>Modifica Risultati Test A
                        @endif
                    @else
                        <i class="fas fa-vial me-2"></i>Esecuzione Test A: Controllo del pH
                    @endif
                </h3>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ $is_edit ? route('test-a.update', $test_a_result->id) : route('test-a.store', $acceptance->id) }}" class="needs-validation" novalidate>
                    @csrf
                    @if($is_edit) @method('PUT') @endif

                    <!-- SEZIONE DATI EREDITATI -->
                    <fieldset class="mb-4">
                        <legend class="h5">Dati di Riferimento</legend>
                        <div class="row p-3 bg-light border rounded">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Lotto</label>
                                <p class="form-control-plaintext">{{ $acceptance->lotto }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">N. Accettazione</label>
                                <p class="form-control-plaintext">{{ $acceptance->acceptance_number }}</p>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SEZIONE COMPILAZIONE TEST -->
                    <fieldset class="mb-4">
                        <legend class="h5">Dati della Prova</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="test_date" class="form-label">Data Prova</label>
                                <input type="date" class="form-control" id="test_date" name="test_date" value="{{ old('test_date', $is_edit ? \Carbon\Carbon::parse($test_a_result->test_date)->format('Y-m-d') : date('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label for="operator" class="form-label">Operatore</label>
                                <input type="text" class="form-control" id="operator" name="operator" value="{{ $currentUser['operatore'] ?? '' }}" readonly {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                            <div class="col-12">
                                <label for="ph_value_slider" class="form-label">Misura pH</label>
                                <div class="ph-slider-container p-3 border rounded">
                                    <i class="fas fa-tint text-muted"></i>
                                    @php $ph_value = old('ph_value', $is_edit ? $test_a_result->ph_value : '7.0'); @endphp
                                    <input type="range" class="form-range" id="ph_value_slider" min="0" max="14" step="0.1" value="{{ $ph_value }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    <input type="number" class="form-control ph-value-input" id="ph_value" name="ph_value" min="0" max="14" step="0.1" value="{{ number_format((float)$ph_value, 1) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SEZIONE ESITO -->
                    <fieldset class="mb-4">
                        <legend class="h5">Esito</legend>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check outcome-radio">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_idoneo" value="idoneo" {{ old('outcome', $is_edit ? $test_a_result->outcome : 'idoneo') == 'idoneo' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label text-success" for="outcome_idoneo">
                                        <i class="fas fa-check-circle icon"></i>
                                        Idoneo
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check outcome-radio">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_non_idoneo" value="non_idoneo" {{ old('outcome', $is_edit ? $test_a_result->outcome : '') == 'non_idoneo' ? 'checked' : '' }} {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label text-danger" for="outcome_non_idoneo">
                                        <i class="fas fa-times-circle icon"></i>
                                        Non Idoneo
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" id="non-compliance-section" style="display: none;">
                            <label for="non_compliance_ref" class="form-label">Riferimento Non Conformità</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-exclamation-triangle"></i></span>
                                <input type="text" class="form-control" id="non_compliance_ref" name="non_compliance_ref" placeholder="Inserire riferimento NC" value="{{ old('non_compliance_ref', $is_edit ? $test_a_result->non_compliance_ref : '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SEZIONE MOTIVAZIONE MODIFICA (solo in edit) -->
                    @if($is_edit && !$is_readonly)
                    <fieldset class="mb-4">
                        <legend class="h5">Motivazione della Modifica</legend>
                        <div class="form-group">
                            <label for="modification_reason" class="form-label">La modifica dei risultati di un test già salvato richiede una motivazione (min. 10 caratteri).</label>
                            <textarea class="form-control @error('modification_reason') is-invalid @enderror" id="modification_reason" name="modification_reason" rows="3" required minlength="10">{{ old('modification_reason') }}</textarea>
                            @error('modification_reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <div class="invalid-feedback">Per favore, inserisci una motivazione per la modifica.</div>
                            @enderror
                        </div>
                    </fieldset>
                    @endif

                    <!-- SEZIONE VALIDAZIONE (per ora disabilitata) -->
                    <fieldset class="mb-4">
                        <legend class="h5">Validazione</legend>
                        <div class="row g-3 p-3 bg-light border rounded">
                            @if($is_edit && $test_a_result->rl_signature_id)
                                @php
                                    $validatorName = $usersMap[$test_a_result->rl_signature_id]['operatore'] ?? 'N/D';
                                    $validationDate = $test_a_result->rl_signed_at ? \Carbon\Carbon::parse($test_a_result->rl_signed_at)->format('d/m/Y H:i') : '';
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
                            @if($is_readonly)
                                <i class="fas fa-arrow-left me-2"></i>Torna all'elenco
                            @else
                                <i class="fas fa-times me-2"></i>Annulla
                            @endif
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
            // Gestione sincronizzata slider e input numerico per il pH
            const phSlider = document.getElementById('ph_value_slider');
            const phInput = document.getElementById('ph_value');

            // Aggiorna l'input numerico quando lo slider viene mosso
            phSlider.addEventListener('input', function() {
                phInput.value = parseFloat(this.value).toFixed(1);
            });

            // Aggiorna lo slider quando si scrive nell'input numerico
            phInput.addEventListener('input', function() {
                let value = parseFloat(this.value);
                // Controlla che il valore sia un numero valido nel range
                if (!isNaN(value) && value >= 0 && value <= 14) {
                    phSlider.value = value;
                }
            });

            // Formatta e valida il valore quando l'utente lascia il campo
            phInput.addEventListener('blur', function() {
                let value = isNaN(parseFloat(this.value)) ? 7.0 : parseFloat(this.value);
                if (value < 0) value = 0;
                if (value > 14) value = 14;
                this.value = value.toFixed(1);
                phSlider.value = value;
            });

            // Gestione visibilità campo Non Conformità
            const outcomeRadios = document.querySelectorAll('input[name="outcome"]');
            const nonComplianceSection = document.getElementById('non-compliance-section');
            const nonComplianceInput = document.getElementById('non_compliance_ref');

            function toggleNonCompliance(isInitial) {
                if (document.getElementById('outcome_non_idoneo').checked) {
                    nonComplianceSection.style.display = 'block';
                    nonComplianceInput.required = true;
                } else {
                    nonComplianceSection.style.display = 'none';
                    nonComplianceInput.required = false;
                    nonComplianceInput.value = ''; // Pulisci il campo se si torna a Idoneo
                    if (!isInitial) {
                        nonComplianceInput.value = ''; // Pulisci il campo se si torna a Idoneo
                    }
                }
            }

            outcomeRadios.forEach(radio => radio.addEventListener('change', toggleNonCompliance));

            // Esegui al caricamento per lo stato iniziale
            toggleNonCompliance(true);

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
        });
    </script>
</body>
</html>