@php
    $is_edit = isset($test_a_result);
    $is_readonly = $is_readonly ?? false;
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🔬</text></svg>">
    <title>{{ $is_readonly ? 'Visualizza' : ($is_edit ? 'Modifica' : 'Esecuzione') }} Test A - Controllo pH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .ph-slider-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .ph-slider-container input[type=range] {
            flex-grow: 1;
        }
        .ph-value-display {
            font-weight: bold;
            font-size: 1.2rem;
            min-width: 50px;
            text-align: center;
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
        <div class="card">
            <div class="card-header {{ $is_readonly ? 'bg-info text-dark' : ($is_edit ? 'bg-warning text-dark' : 'bg-primary text-white') }}">
                <h3>
                    @if($is_readonly)
                        <i class="fas fa-eye me-2"></i>Visualizza Risultati Test A: Controllo del pH
                    @else
                        <i class="fas fa-vial me-2"></i>{{ $is_edit ? 'Modifica Risultati' : 'Esecuzione' }} Test A: Controllo del pH
                    @endif
                </h3>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ $is_edit ? route('test-a.update', $test_a_result->id) : route('test-a.store', $acceptance->id) }}">
                    @csrf
                    @if($is_edit)
                        @method('PUT')
                    @endif

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
                                <input type="date" class="form-control" id="test_date" name="test_date" value="{{ old('test_date', $is_edit ? $test_a_result->test_date : date('Y-m-d')) }}" required {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label for="operator" class="form-label">Operatore</label>
                                <input type="text" class="form-control" id="operator" name="operator" value="{{ $currentUser['operatore'] ?? '' }}" readonly>
                            </div>
                            <div class="col-12">
                                <label for="ph_value_slider" class="form-label">Misura pH</label>
                                @php
                                    $ph_value = old('ph_value', $test_a_result->ph_value ?? '7.0');
                                @endphp
                                <div class="ph-slider-container p-3 border rounded">
                                    <i class="fas fa-tint text-muted"></i>
                                    <input type="range" class="form-range" id="ph_value_slider" min="0" max="14" step="0.1" value="{{ $ph_value }}" {{ $is_readonly ? 'disabled' : '' }}>
                                    <span id="ph_value_display" class="ph-value-display bg-primary-subtle border rounded px-2">{{ $ph_value }}</span>
                                    <input type="hidden" id="ph_value" name="ph_value" value="{{ $ph_value }}">
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SEZIONE ESITO -->
                    <fieldset class="mb-4">
                        <legend class="h5">Esito</legend>
                        @php
                            $outcome = old('outcome', $test_a_result->outcome ?? 'idoneo');
                        @endphp
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check outcome-radio">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_idoneo" value="idoneo" @if($outcome === 'idoneo') checked @endif {{ $is_readonly ? 'disabled' : '' }}>
                                    <label class="form-check-label text-success" for="outcome_idoneo">
                                        <i class="fas fa-check-circle icon"></i>
                                        Idoneo
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check outcome-radio">
                                    <input class="form-check-input" type="radio" name="outcome" id="outcome_non_idoneo" value="non_idoneo" @if($outcome === 'non_idoneo') checked @endif {{ $is_readonly ? 'disabled' : '' }}>
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
                                <input type="text" class="form-control" id="non_compliance_ref" name="non_compliance_ref" placeholder="Inserire riferimento NC" value="{{ old('non_compliance_ref', $test_a_result->non_compliance_ref ?? '') }}" {{ $is_readonly ? 'disabled' : '' }}>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SEZIONE VALIDAZIONE (per ora disabilitata) -->
                    <fieldset class="mb-4">
                        <legend class="h5">Validazione</legend>
                        <div class="row g-3 p-3 bg-light border rounded">
                             <div class="col-md-6">
                                <label for="validator" class="form-label">Validato da RL</label>
                                <input type="text" class="form-control" id="validator" name="validator" placeholder="Validazione pendente" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="validation_date" class="form-label">in data</label>
                                <input type="date" class="form-control" id="validation_date" name="validation_date" disabled>
                            </div>
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
                            <button type="submit" class="btn {{ $is_edit ? 'btn-warning' : 'btn-primary' }} btn-lg">
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
            // Gestione slider pH
            const phSlider = document.getElementById('ph_value_slider');
            const phDisplay = document.getElementById('ph_value_display');
            const phHiddenInput = document.getElementById('ph_value');

            phSlider.addEventListener('input', function() {
                phDisplay.textContent = this.value;
                phHiddenInput.value = this.value;
            });

            // Gestione visibilità campo Non Conformità
            const outcomeRadios = document.querySelectorAll('input[name="outcome"]');
            const nonComplianceSection = document.getElementById('non-compliance-section');
            const nonComplianceInput = document.getElementById('non_compliance_ref');

            function toggleNonCompliance() {
                if (document.getElementById('outcome_non_idoneo').checked) {
                    nonComplianceSection.style.display = 'block';
                    nonComplianceInput.required = true;
                } else {
                    nonComplianceSection.style.display = 'none';
                    nonComplianceInput.required = false;
                    nonComplianceInput.value = ''; // Pulisci il campo se si torna a Idoneo
                }
            }

            outcomeRadios.forEach(radio => radio.addEventListener('change', toggleNonCompliance));

            // Esegui al caricamento per lo stato iniziale
            toggleNonCompliance();
        });
    </script>
</body>
</html>
